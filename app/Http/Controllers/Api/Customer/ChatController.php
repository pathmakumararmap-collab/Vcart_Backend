<?php

namespace App\Http\Controllers\Api\Customer;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ConversationResource;
use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ChatController extends Controller
{
    #[OA\Get(
        path: '/customer/chat',
        tags: ['Customer Chat'],
        summary: "Get (or start) the customer's support conversation, with message history",
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Conversation and messages')],
    )]
    public function show(Request $request): JsonResponse
    {
        $conversation = $this->currentConversation($request);
        $conversation->load(['messages.sender']);

        return response()->json([
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'messages' => ChatMessageResource::collection($conversation->messages),
            ],
        ]);
    }

    #[OA\Post(
        path: '/customer/chat/read',
        tags: ['Customer Chat'],
        summary: 'Mark the conversation as read (call this when the customer opens the chat panel)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Marked as read')],
    )]
    public function markRead(Request $request): JsonResponse
    {
        $conversation = $this->currentConversation($request);
        $conversation->update(['customer_read_at' => now()]);

        return response()->json(['data' => new ConversationResource($conversation)]);
    }

    #[OA\Post(
        path: '/customer/chat/messages',
        tags: ['Customer Chat'],
        summary: 'Send a message to support',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Message sent')],
    )]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation = $this->currentConversation($request);

        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'customer_read_at' => now(),
            'status' => 'open',
        ]);

        $message->load('sender');
        broadcast(new ChatMessageSent($message));

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }

    /**
     * A customer has at most one active conversation — this finds it, or
     * opens a new one on their first message.
     */
    private function currentConversation(Request $request): Conversation
    {
        return Conversation::query()
            ->where('user_id', $request->user()->id)
            ->open()
            ->latest('last_message_at')
            ->first()
            ?? Conversation::query()->create(['user_id' => $request->user()->id, 'status' => 'open']);
    }
}
