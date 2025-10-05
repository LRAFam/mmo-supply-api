<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscordBotAuth
{
    /**
     * Handle an incoming request from the Discord bot.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Discord-Bot-Key');

        // Check if API key matches the one in .env
        if (!$apiKey || $apiKey !== config('services.discord.bot_api_key')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing Discord bot API key'
            ], 401);
        }

        return $next($request);
    }
}
