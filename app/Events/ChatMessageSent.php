<?php

namespace App\Events;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ChatMessage $message) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        $channels = [new PrivateChannel('conversation.'.$conversation->id)];

        // Let the admin inbox know a message landed in some conversation,
        // without every admin having to subscribe to every conversation
        // individually — the inbox list re-fetches/reorders on this event.
        if ($this->message->sender_id === $conversation->user_id) {
            $channels[] = new PrivateChannel('admin.chat-inbox');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => (new ChatMessageResource($this->message))->resolve(),
            'conversation_id' => $this->message->conversation_id,
        ];
    }
}
