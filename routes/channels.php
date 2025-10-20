<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Conversation channels - user can access if they're part of the conversation
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    error_log("Channel auth for conversation.$conversationId - User: $user->id");

    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        error_log("Channel auth FAILED: Conversation $conversationId not found");
        return false;
    }

    // User can access if they're either user_one or user_two in the conversation
    $isAuthorized = (int) $user->id === (int) $conversation->user_one_id
        || (int) $user->id === (int) $conversation->user_two_id;

    if (!$isAuthorized) {
        error_log("Channel auth FAILED: User $user->id not part of conversation $conversationId (users: $conversation->user_one_id, $conversation->user_two_id)");
        return false;
    }

    error_log("Channel auth SUCCESS: User $user->id authorized for conversation $conversationId");

    // Return user data for successful authorization
    return ['id' => $user->id, 'name' => $user->name];
});
