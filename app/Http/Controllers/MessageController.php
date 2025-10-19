<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\AIResponseService;
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

        // Ensure AI Agent conversation exists
        $this->ensureAIConversationExists($user);

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
                    'is_ai_agent' => $otherUser->role === 'system',
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'username' => $otherUser->username ?? $otherUser->name,
                        'role' => $otherUser->role,
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
                    'metadata' => $message->metadata,
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
            'message' => 'nullable|string|max:5000',
            'order_id' => 'nullable|exists:orders,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max per file
        ]);

        // Require either message or attachments
        if (empty($request->message) && empty($request->attachments)) {
            return response()->json(['error' => 'Message or attachments required'], 400);
        }

        $user = $request->user();
        $recipientId = $request->recipient_id;

        // Can't message yourself
        if ($user->id === $recipientId) {
            return response()->json(['error' => 'Cannot message yourself'], 400);
        }

        // Find or create conversation
        $conversation = Conversation::findOrCreate($user->id, $recipientId, $request->order_id);

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('message-attachments', 's3');
                $url = \Storage::disk('s3')->url($path);

                $attachments[] = [
                    'type' => 'image',
                    'url' => $url,
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Prepare metadata
        $metadata = [];
        if (!empty($attachments)) {
            $metadata['attachments'] = $attachments;
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message ?? '',
            'type' => 'user',
            'metadata' => empty($metadata) ? null : $metadata,
        ]);

        // Update conversation last message time
        $conversation->update(['last_message_at' => now()]);

        // Check if messaging AI Agent - if so, generate response
        $recipient = User::find($recipientId);
        if ($recipient && $recipient->role === 'system') {
            $aiResponseService = app(AIResponseService::class);
            $aiResponse = $aiResponseService->generateResponse($user, $request->message);

            // Create AI response message
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $recipientId,
                'message' => $aiResponse,
                'type' => 'ai_agent',
                'is_read' => false,
            ]);

            // Update conversation time again
            $conversation->update(['last_message_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'conversation_id' => $conversation->id,
                'message' => $message->message,
                'metadata' => $message->metadata,
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'is_mine' => true,
                'is_read' => false,
                'created_at' => $message->created_at,
            ],
            'ai_response' => isset($aiMessage) ? [
                'id' => $aiMessage->id,
                'message' => $aiMessage->message,
                'created_at' => $aiMessage->created_at,
            ] : null,
            'conversation' => [
                'id' => $conversation->id,
                'order_id' => $conversation->order_id,
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

    /**
     * Ensure AI Agent conversation exists for user
     */
    private function ensureAIConversationExists(User $user): void
    {
        // Get AI Agent user
        $aiAgent = User::where('role', 'system')
            ->where('email', 'agent@mmosupply.com')
            ->first();

        if (!$aiAgent) {
            return; // AI Agent not found
        }

        // Check if conversation already exists
        $conversationExists = Conversation::where(function ($query) use ($user, $aiAgent) {
            $query->where('user_one_id', $user->id)
                ->where('user_two_id', $aiAgent->id);
        })->orWhere(function ($query) use ($user, $aiAgent) {
            $query->where('user_one_id', $aiAgent->id)
                ->where('user_two_id', $user->id);
        })->exists();

        if ($conversationExists) {
            return; // Conversation already exists
        }

        // Create conversation with AI Agent
        $conversation = Conversation::create([
            'user_one_id' => $user->id,
            'user_two_id' => $aiAgent->id,
            'subject' => 'Chat with MMO Supply Assistant',
            'last_message_at' => now(),
        ]);

        // Send welcome message from AI Agent
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $aiAgent->id,
            'type' => 'ai_agent',
            'message' => "👋 Hi {$user->name}! I'm your MMO Supply Assistant.\n\n" .
                        "I'm here to help you 24/7! I can:\n" .
                        "• Track your orders and listings\n" .
                        "• Check your stats and achievements\n" .
                        "• Answer questions about the platform\n" .
                        "• Provide personalized recommendations\n\n" .
                        "Just ask me anything - try \"What's my wallet balance?\" or \"How many achievement points do I have?\"",
            'is_read' => false,
        ]);
    }

    /**
     * Send a system message about an order (static helper for OrderController)
     */
    public static function sendOrderSystemMessage(int $buyerId, int $sellerId, int $orderId, string $message, string $type = 'order_created'): void
    {
        // Find or create conversation between buyer and seller
        $conversation = Conversation::findOrCreate($buyerId, $sellerId, $orderId);

        // Create system message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyerId, // System message sent from buyer's side
            'message' => $message,
            'type' => 'system',
            'metadata' => [
                'order_id' => $orderId,
                'system_type' => $type,
            ],
        ]);

        // Update conversation last message time
        $conversation->update(['last_message_at' => now()]);
    }
}
