<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Game::query();

        // Search by name
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        // Filter by active
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Always include provider count
        $query->withCount(['providers', 'items', 'currencies', 'accounts', 'services']);

        $games = $query->orderBy('title')->get();

        // Map provider_count from providers_count
        $games->transform(function ($game) {
            $game->provider_count = $game->providers_count;
            return $game;
        });

        return response()->json($games);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $game = Game::with([
            'items' => function($q) {
                $q->where('is_active', true)->where('stock', '>', 0)->without(['user', 'game']);
            },
            'currencies' => function($q) {
                $q->where('is_active', true)->where('stock', '>', 0)->without(['user', 'game']);
            },
            'accounts' => function($q) {
                $q->where('is_active', true)->where('stock', '>', 0)->without(['user', 'game']);
            },
            'services' => function($q) {
                $q->where('is_active', true)->without(['user', 'game']);
            },
            'providers' => function($q) {
                $q->with('user')->orderBy('rating', 'desc');
            }
        ])
        ->withCount('providers')
        ->findOrFail($id);

        // Update provider_count from dynamic count
        $game->provider_count = $game->providers_count;

        return response()->json($game);
    }
}
