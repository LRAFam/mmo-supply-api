<?php

namespace App\Http\Controllers;

use App\Models\Season;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    /**
     * Get current active season
     */
    public function current(): JsonResponse
    {
        $season = Season::current();

        if (!$season) {
            return response()->json([
                'message' => 'No active season found'
            ], 404);
        }

        return response()->json([
            'id' => $season->id,
            'season_number' => $season->season_number,
            'name' => $season->name,
            'description' => $season->description,
            'start_date' => $season->start_date,
            'end_date' => $season->end_date,
            'status' => $season->status,
            'prize_pool' => $season->prize_pool,
            'features' => $season->features,
            'days_remaining' => $season->daysRemaining(),
        ]);
    }

    /**
     * Get all seasons
     */
    public function index(): JsonResponse
    {
        $seasons = Season::orderBy('season_number', 'desc')->get();

        $seasonsData = $seasons->map(function ($season) {
            return [
                'id' => $season->id,
                'season_number' => $season->season_number,
                'name' => $season->name,
                'status' => $season->status,
                'start_date' => $season->start_date,
                'end_date' => $season->end_date,
                'prize_pool' => $season->prize_pool,
                'stats' => $season->getStats(),
            ];
        });

        return response()->json($seasonsData);
    }

    /**
     * Get specific season details
     */
    public function show($id): JsonResponse
    {
        $season = Season::find($id);

        if (!$season) {
            return response()->json([
                'message' => 'Season not found'
            ], 404);
        }

        return response()->json([
            'id' => $season->id,
            'season_number' => $season->season_number,
            'name' => $season->name,
            'description' => $season->description,
            'start_date' => $season->start_date,
            'end_date' => $season->end_date,
            'status' => $season->status,
            'prize_pool' => $season->prize_pool,
            'features' => $season->features,
        ]);
    }

    /**
     * Get season statistics
     */
    public function stats($id): JsonResponse
    {
        $season = Season::find($id);

        if (!$season) {
            return response()->json([
                'message' => 'Season not found'
            ], 404);
        }

        return response()->json($season->getStats());
    }

    /**
     * Get season leaderboard
     */
    public function leaderboard($id): JsonResponse
    {
        $season = Season::find($id);

        if (!$season) {
            return response()->json([
                'message' => 'Season not found'
            ], 404);
        }

        return response()->json([
            'season_id' => $season->id,
            'leaderboard' => $season->getLeaderboard(),
        ]);
    }

    /**
     * Get user's season participation history
     */
    public function userSeasons($userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $participations = $user->seasonParticipations()
            ->with('season:id,season_number,name')
            ->orderBy('season_id', 'desc')
            ->get()
            ->map(function ($participation) {
                return [
                    'season_id' => $participation->season_id,
                    'season_number' => $participation->season->season_number,
                    'season_name' => $participation->season->name,
                    'rank' => $participation->rank,
                    'total_earned' => $participation->total_earned,
                    'achievements_unlocked' => $participation->achievements_unlocked,
                ];
            });

        return response()->json($participations);
    }
}
