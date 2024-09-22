<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Log in the user after registration
        Auth::login($user);

        return response()->json(['user' => $user, 'message' => 'User registered successfully'], 201);
    }

    /**
     * Login user (cookie-based authentication).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('name', 'password'))) {
            $user = Auth::user();
            return response()->json(['user' => $user]);
        }

        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Refresh the session (if needed).
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            Auth::login($user);
        }
        return response()->json(['message' => 'Session refreshed']);
    }
}
