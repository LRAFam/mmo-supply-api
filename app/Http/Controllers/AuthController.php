<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Redirect the user to the Discord provider for authentication.
     */
    public function redirectToProvider(): RedirectResponse
    {
        try {
            return Socialite::driver('discord')
                ->redirectUrl(config('services.discord.redirect'))
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Discord authentication error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Unable to redirect to Discord provider.');
        }
    }

    /**
     * Handle the callback from the provider after authentication.
     */
    public function handleProviderCallback(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $user = Socialite::driver('discord')->user();
            $existingUser = User::where('discord_id', $user->id)->first();

            if ($existingUser) {
                Auth::login($existingUser);
            } else {
                $newUser = User::create([
                    'discord_id' => $user->id,
                    'nickname' => $user->nickname,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'email' => $user->email,
                ]);
                Auth::login($newUser);
            }

            return redirect('/'); // Redirect to your desired route

        } catch (\Exception $e) {
            Log::error('Discord authentication error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Unable to fetch Discord user data.'], 400);
        }
    }

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // Create wallet for new user
            $user->wallet()->create(['balance' => 0]);

            // Handle referral code if provided
            if ($request->referral_code) {
                $referrer = User::where('referral_code', $request->referral_code)->first();

                if ($referrer && $referrer->id !== $user->id) {
                    // Update user's referred_by
                    $user->update(['referred_by' => $referrer->id]);

                    // Create referral record
                    \App\Models\Referral::create([
                        'referrer_id' => $referrer->id,
                        'referred_id' => $user->id,
                        'referral_code' => $referrer->referral_code,
                    ]);

                    // Increment referrer's total referrals count
                    $referrer->increment('total_referrals');
                }
            }

            // Send verification email
            $this->sendVerificationEmail($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'is_seller' => $user->is_seller ?? false,
                    'subscription_tier' => $user->getSubscriptionTier(),
                    'wallet' => [
                        'balance' => 0,
                    ],
                ],
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Login user (token-based authentication).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        // Revoke existing tokens for security
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Load wallet relationship
        $user->load('wallet');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'is_seller' => $user->is_seller ?? false,
                'subscription_tier' => $user->getSubscriptionTier(),
                'wallet' => [
                    'balance' => $user->wallet->balance ?? 0,
                ],
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Send verification email to user.
     */
    private function sendVerificationEmail(User $user): void
    {
        $token = Str::random(60);

        // Store verification token
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $verificationUrl = config('app.frontend_url') . '/auth/verify-email?token=' . $token . '&email=' . urlencode($user->email);

        Mail::to($user->email)->send(new \App\Mail\VerifyEmailMail($user, $verificationUrl));
    }

    /**
     * Resend verification email.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified',
            ], 400);
        }

        $this->sendVerificationEmail($user);

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully',
        ]);
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified',
            ], 400);
        }

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification link',
            ], 400);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($tokenRecord->created_at) > 60) {
            return response()->json([
                'success' => false,
                'message' => 'Verification link has expired',
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Delete verification token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if user exists
            return response()->json([
                'success' => true,
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $resetUrl = config('app.frontend_url') . '/auth/reset-password?token=' . $token . '&email=' . urlencode($user->email);

        Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user->email, $resetUrl));

        return response()->json([
            'success' => true,
            'message' => 'If an account exists with that email, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reset link',
            ], 400);
        }

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenRecord || !Hash::check($request->token, $tokenRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset link',
            ], 400);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($tokenRecord->created_at) > 60) {
            return response()->json([
                'success' => false,
                'message' => 'Reset link has expired',
            ], 400);
        }

        // Update password
        $user->password = bcrypt($request->password);
        $user->save();

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully! You can now login with your new password.',
        ]);
    }
}
