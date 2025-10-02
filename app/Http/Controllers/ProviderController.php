<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Provider::with(['user', 'game']);

        // Filter by game
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Filter by verified status
        if ($request->has('verified')) {
            $query->where('is_verified', $request->boolean('verified'));
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'rating');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $providers = $query->paginate($request->get('per_page', 20));

        return response()->json($providers);
    }

    public function show($id): JsonResponse
    {
        $provider = Provider::with(['user', 'game'])->findOrFail($id);

        // Get provider's products
        $user = $provider->user;
        $gameId = $provider->game_id;

        $provider->items = $user->items()->where('game_id', $gameId)->where('is_active', true)->get();
        $provider->currencies = $user->currencies()->where('game_id', $gameId)->where('is_active', true)->get();
        $provider->accounts = $user->accounts()->where('game_id', $gameId)->where('is_active', true)->get();
        $provider->services = $user->services()->where('game_id', $gameId)->where('is_active', true)->get();

        return response()->json($provider);
    }
}
