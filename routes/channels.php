<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// A conversation's channel is only accessible by the customer who owns it,
// or by any admin with permission to manage support chat.
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return $user->id === $conversation->user_id || $user->can('chat.manage');
});

// The admin inbox channel — notifies every connected admin when a new
// customer message arrives in any conversation, so the inbox list can
// live-reorder/refresh without each admin subscribing per-conversation.
Broadcast::channel('admin.chat-inbox', function ($user) {
    return $user->can('chat.manage');
});
