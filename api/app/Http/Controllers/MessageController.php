<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function getConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userOne', 'userTwo', 'latestMessage' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherUser = $conversation->getOtherUser($user->id);
                $latestMessage = $conversation->latestMessage->first();

                return [
                    'id' => $conversation->id,
                    'order_id' => $conversation->order_id,
                    'subject' => $conversation->subject,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                    ],
                    'latest_message' => $latestMessage ? [
                        'message' => $latestMessage->message,
                        'created_at' => $latestMessage->created_at,
                        'is_mine' => $latestMessage->sender_id === $user->id,
                    ] : null,
                    'unread_count' => $conversation->getUnreadCount($user->id),
                    'last_message_at' => $conversation->last_message_at,
                ];
            });

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('id', $conversationId)
            ->where(function ($query) use ($user) {
                $query->where('user_one_id', $user->id)
                    ->orWhere('user_two_id', $user->id);
            })
            ->with(['userOne', 'userTwo', 'messages.sender'])
            ->firstOrFail();

        // Mark messages as read
        $conversation->markAsRead($user->id);

        $otherUser = $conversation->getOtherUser($user->id);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'order_id' => $conversation->order_id,
                'subject' => $conversation->subject,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                ],
            ],
            'messages' => $conversation->messages->map(function ($message) use ($user) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'is_mine' => $message->sender_id === $user->id,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at,
                ];
            }),
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $user = $request->user();
        $recipientId = $request->recipient_id;

        // Can't message yourself
        if ($user->id === $recipientId) {
            return response()->json(['error' => 'Cannot message yourself'], 400);
        }

        // Find or create conversation
        $conversation = Conversation::findOrCreate($user->id, $recipientId, $request->order_id);

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
        ]);

        // Update conversation last message time
        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'conversation_id' => $conversation->id,
                'message' => $message->message,
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'is_mine' => true,
                'is_read' => false,
                'created_at' => $message->created_at,
            ],
        ], 201);
    }

    /**
     * Start a conversation with a user (or get existing one)
     */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $user = $request->user();
        $otherUserId = $request->user_id;

        if ($user->id === $otherUserId) {
            return response()->json(['error' => 'Cannot start conversation with yourself'], 400);
        }

        $conversation = Conversation::findOrCreate($user->id, $otherUserId, $request->order_id);
        $otherUser = $conversation->getOtherUser($user->id);

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conversation->id,
                'order_id' => $conversation->order_id,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                ],
            ],
        ]);
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadCount = Message::whereHas('conversation', function ($query) use ($user) {
            $query->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id);
        })
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }
}
