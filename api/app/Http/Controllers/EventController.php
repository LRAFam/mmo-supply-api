<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Get all events
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->with('game');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by game
        if ($request->has('game_id')) {
            $query->where('game_id', $request->game_id);
        }

        // Featured only
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $events = $query->orderBy('starts_at', 'desc')->get()->map(function ($event) use ($request) {
            $data = [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'description' => $event->description,
                'type' => $event->type,
                'game' => $event->game,
                'banner_image' => $event->banner_image,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'status' => $event->status,
                'max_participants' => $event->max_participants,
                'current_participants' => $event->participants()->count(),
                'winner_count' => $event->winner_count,
                'prizes' => $event->prizes,
                'is_featured' => $event->is_featured,
                'is_active' => $event->isActive(),
                'is_upcoming' => $event->isUpcoming(),
                'has_available_spots' => $event->hasAvailableSpots(),
            ];

            if ($user = $request->user()) {
                $data['is_participating'] = $event->isUserParticipating($user);
                $data['can_participate'] = $event->canUserParticipate($user);
            }

            return $data;
        });

        return response()->json(['events' => $events]);
    }

    /**
     * Get a single event with details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::with(['game', 'participants.user'])->findOrFail($id);

        $data = [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'description' => $event->description,
            'type' => $event->type,
            'game' => $event->game,
            'banner_image' => $event->banner_image,
            'starts_at' => $event->starts_at,
            'ends_at' => $event->ends_at,
            'status' => $event->status,
            'max_participants' => $event->max_participants,
            'current_participants' => $event->participants()->count(),
            'winner_count' => $event->winner_count,
            'prizes' => $event->prizes,
            'rules' => $event->rules,
            'requirements' => $event->requirements,
            'is_featured' => $event->is_featured,
            'is_active' => $event->isActive(),
            'is_upcoming' => $event->isUpcoming(),
            'is_completed' => $event->isCompleted(),
            'has_available_spots' => $event->hasAvailableSpots(),
        ];

        if ($user = $request->user()) {
            $data['is_participating'] = $event->isUserParticipating($user);
            $data['can_participate'] = $event->canUserParticipate($user);

            $participant = $event->participants()->where('user_id', $user->id)->first();
            if ($participant) {
                $data['participant_data'] = [
                    'joined_at' => $participant->joined_at,
                    'score' => $participant->score,
                    'rank' => $participant->rank,
                    'status' => $participant->status,
                    'prize_data' => $participant->prize_data,
                    'prize_claimed' => $participant->prize_claimed,
                ];
            }
        }

        // Include leaderboard for tournaments
        if ($event->type === 'tournament') {
            $data['leaderboard'] = $event->participants()
                ->with('user:id,name')
                ->orderBy('score', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($participant, $index) {
                    return [
                        'rank' => $index + 1,
                        'user' => $participant->user->name,
                        'score' => $participant->score,
                        'status' => $participant->status,
                    ];
                });
        }

        // Include winners if completed
        if ($event->isCompleted()) {
            $data['winners'] = $event->winners()->with('user:id,name')->get()->map(function ($winner) {
                return [
                    'rank' => $winner->rank,
                    'user' => $winner->user->name,
                    'score' => $winner->score,
                    'prize' => $winner->prize_data,
                ];
            });
        }

        return response()->json(['event' => $data]);
    }

    /**
     * Register for an event
     */
    public function register(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $event = Event::findOrFail($id);

        if (!$event->canUserParticipate($user)) {
            return response()->json([
                'message' => 'You cannot participate in this event.',
                'reasons' => $this->getCannotParticipateReasons($event, $user),
            ], 403);
        }

        if ($event->registerUser($user)) {
            return response()->json([
                'message' => 'Successfully registered for the event!',
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'type' => $event->type,
                    'starts_at' => $event->starts_at,
                ],
            ]);
        }

        return response()->json(['message' => 'Failed to register for the event.'], 500);
    }

    /**
     * Get user's events
     */
    public function userEvents(Request $request): JsonResponse
    {
        $user = $request->user();

        $participatingEvents = $user->events()
            ->with('game')
            ->orderBy('event_participants.joined_at', 'desc')
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'name' => $event->name,
                    'type' => $event->type,
                    'game' => $event->game,
                    'status' => $event->status,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                    'participant_status' => $event->pivot->status,
                    'score' => $event->pivot->score,
                    'rank' => $event->pivot->rank,
                    'prize_data' => $event->pivot->prize_data,
                    'prize_claimed' => $event->pivot->prize_claimed,
                ];
            });

        return response()->json([
            'events' => $participatingEvents,
            'stats' => [
                'total_participated' => $participatingEvents->count(),
                'won' => $participatingEvents->where('participant_status', 'winner')->count(),
                'in_progress' => $participatingEvents->whereIn('status', ['active', 'upcoming'])->count(),
            ],
        ]);
    }

    /**
     * Get active events
     */
    public function active(): JsonResponse
    {
        $events = Event::getActive();

        return response()->json(['events' => $events]);
    }

    /**
     * Get upcoming events
     */
    public function upcoming(): JsonResponse
    {
        $events = Event::getUpcoming();

        return response()->json(['events' => $events]);
    }

    /**
     * Get featured events
     */
    public function featured(): JsonResponse
    {
        $events = Event::getFeatured();

        return response()->json(['events' => $events]);
    }

    /**
     * Claim prize (for winners)
     */
    public function claimPrize(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $event = Event::findOrFail($id);

        $participant = $event->participants()->where('user_id', $user->id)->first();

        if (!$participant) {
            return response()->json(['message' => 'You did not participate in this event.'], 403);
        }

        if (!$participant->isWinner()) {
            return response()->json(['message' => 'You are not a winner of this event.'], 403);
        }

        if ($participant->prize_claimed) {
            return response()->json(['message' => 'Prize already claimed.'], 400);
        }

        if ($participant->claimPrize()) {
            return response()->json([
                'message' => 'Prize claimed successfully!',
                'prize' => $participant->prize_data,
            ]);
        }

        return response()->json(['message' => 'Failed to claim prize.'], 500);
    }

    /**
     * Get event leaderboard
     */
    public function leaderboard(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $leaderboard = $event->participants()
            ->with('user:id,name')
            ->orderBy('score', 'desc')
            ->get()
            ->map(function ($participant, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $participant->user->id,
                    'user_name' => $participant->user->name,
                    'score' => $participant->score,
                    'status' => $participant->status,
                ];
            });

        return response()->json([
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'type' => $event->type,
            ],
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Get reasons why user cannot participate
     */
    private function getCannotParticipateReasons(Event $event, $user): array
    {
        $reasons = [];

        if (!$event->isActive() && !$event->isUpcoming()) {
            $reasons[] = 'Event is not active or upcoming';
        }

        if ($event->isUserParticipating($user)) {
            $reasons[] = 'You are already participating';
        }

        if (!$event->hasAvailableSpots()) {
            $reasons[] = 'Event is full';
        }

        if ($event->requirements) {
            foreach ($event->requirements as $type => $value) {
                switch ($type) {
                    case 'min_purchases':
                        $purchases = $user->orders()->where('payment_status', 'completed')->count();
                        if ($purchases < $value) {
                            $reasons[] = "Requires at least {$value} purchases (you have {$purchases})";
                        }
                        break;

                    case 'min_spent':
                        $spent = $user->orders()->where('payment_status', 'completed')->sum('total');
                        if ($spent < $value) {
                            $reasons[] = "Requires at least \${$value} spent (you have \${$spent})";
                        }
                        break;

                    case 'is_seller':
                        if (!$user->is_seller) {
                            $reasons[] = 'Must be a verified seller';
                        }
                        break;
                }
            }
        }

        return $reasons;
    }
}
