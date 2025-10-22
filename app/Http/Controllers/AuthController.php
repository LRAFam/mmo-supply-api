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
    public function redirectToProvider(Request $request): RedirectResponse
    {
        try {
            $driver = Socialite::driver('discord')
                ->redirectUrl(config('services.discord.redirect'));

            // If user is authenticated and wants to link Discord, pass user_id in state
            if ($request->bearerToken()) {
                try {
                    $user = Auth::guard('sanctum')->user();
                    if ($user) {
                        // Encode user ID in state parameter
                        $state = encrypt(['user_id' => $user->id, 'timestamp' => time()]);
                        $driver->with(['state' => $state]);
                    }
                } catch (\Exception $e) {
                    // If token is invalid, continue as normal login/registration
                    Log::info('Discord auth: Invalid token, proceeding as new login', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $driver->stateless()->redirect();
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
    public function handleProviderCallback(Request $request): JsonResponse
    {
        try {
            $discordUser = Socialite::driver('discord')->stateless()->user();

            // Check if this is a linking scenario via state parameter
            $authenticatedUser = null;
            if ($request->has('state')) {
                try {
                    $stateData = decrypt($request->state);
                    if (isset($stateData['user_id'])) {
                        $authenticatedUser = User::find($stateData['user_id']);
                    }
                } catch (\Exception $e) {
                    Log::warning('Discord callback: Invalid state parameter', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($authenticatedUser) {
                // User is logged in and wants to link Discord to their account

                // Check if Discord is already linked to another account
                $existingDiscordUser = User::where('discord_id', $discordUser->id)
                    ->where('id', '!=', $authenticatedUser->id)
                    ->first();

                if ($existingDiscordUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This Discord account is already linked to another user.',
                    ], 400);
                }

                // Link Discord to current authenticated user
                $authenticatedUser->update([
                    'discord_id' => $discordUser->id,
                    'discord_username' => $discordUser->nickname ?? $discordUser->name,
                    'discord_avatar' => $discordUser->avatar,
                    'discord_banner' => $discordUser->user['banner'] ?? null,
                    'discord_accent_color' => $discordUser->user['accent_color'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Discord account linked successfully!',
                    'user' => [
                        'id' => $authenticatedUser->id,
                        'name' => $authenticatedUser->name,
                        'email' => $authenticatedUser->email,
                        'discord_id' => $authenticatedUser->discord_id,
                        'discord_username' => $authenticatedUser->discord_username,
                        'discord_avatar' => $authenticatedUser->discord_avatar,
                        'discord_banner' => $authenticatedUser->discord_banner,
                        'discord_accent_color' => $authenticatedUser->discord_accent_color,
                        'avatar_url' => $authenticatedUser->getAvatarUrl(),
                        'banner_url' => $authenticatedUser->getBannerUrl(),
                        'accent_color' => $authenticatedUser->getAccentColor(),
                    ],
                ], 200);
            }

            // No authenticated user - this is a login/registration flow
            // Find or create user by discord_id
            $user = User::where('discord_id', $discordUser->id)->first();

            if ($user) {
                // Update Discord info if user exists
                $user->update([
                    'discord_username' => $discordUser->nickname ?? $discordUser->name,
                    'discord_avatar' => $discordUser->avatar,
                    'discord_banner' => $discordUser->user['banner'] ?? null,
                    'discord_accent_color' => $discordUser->user['accent_color'] ?? null,
                ]);
            } else {
                // Create new user with Discord info
                $user = User::create([
                    'discord_id' => $discordUser->id,
                    'discord_username' => $discordUser->nickname ?? $discordUser->name,
                    'discord_avatar' => $discordUser->avatar,
                    'discord_banner' => $discordUser->user['banner'] ?? null,
                    'discord_accent_color' => $discordUser->user['accent_color'] ?? null,
                    'name' => $discordUser->name ?? $discordUser->nickname,
                    'email' => $discordUser->email,
                    'password' => \Hash::make(\Str::random(32)), // Random password for Discord-only users
                    'email_verified_at' => now(), // Discord emails are pre-verified
                ]);
            }

            // Generate API token
            $token = $user->createToken('discord-auth')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'discord_id' => $user->discord_id,
                    'discord_username' => $user->discord_username,
                    'discord_avatar' => $user->discord_avatar,
                    'discord_banner' => $user->discord_banner,
                    'discord_accent_color' => $user->discord_accent_color,
                    'avatar_url' => $user->getAvatarUrl(),
                    'banner_url' => $user->getBannerUrl(),
                    'accent_color' => $user->getAccentColor(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Discord authentication error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to authenticate with Discord. Please try again.',
            ], 400);
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

    /**
     * Unlink Discord account from user.
     */
    public function unlinkDiscord(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        if (!$user->discord_id) {
            return response()->json([
                'success' => false,
                'message' => 'No Discord account linked to this user',
            ], 400);
        }

        try {
            $user->discord_id = null;
            $user->discord_username = null;
            $user->discord_avatar = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Discord account unlinked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Discord unlink error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink Discord account',
            ], 500);
        }
    }
}
