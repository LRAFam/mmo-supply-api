<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Conversation channels - user can access if they're part of the conversation
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        \Log::warning('Broadcasting auth failed: Conversation not found', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
        ]);
        return false;
    }

    // User can access if they're either user_one or user_two in the conversation
    $isAuthorized = (int) $user->id === (int) $conversation->user_one_id
        || (int) $user->id === (int) $conversation->user_two_id;

    if (!$isAuthorized) {
        \Log::warning('Broadcasting auth failed: User not part of conversation', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'user_one_id' => $conversation->user_one_id,
            'user_two_id' => $conversation->user_two_id,
        ]);
    }

    return $isAuthorized;
});
