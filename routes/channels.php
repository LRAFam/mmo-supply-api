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
        return false;
    }

    // User can access if they're either user_one or user_two in the conversation
    return (int) $user->id === (int) $conversation->user_one_id
        || (int) $user->id === (int) $conversation->user_two_id;
});
