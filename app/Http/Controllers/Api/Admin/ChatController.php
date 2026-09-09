<?php

namespace App\Http\Controllers\Api\Admin;

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
        path: '/admin/chat/conversations',
        tags: ['Admin Chat'],
        summary: 'List support conversations, most recently active first',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Conversation list')],
    )]
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('chat.manage'), 403);

        $conversations = Conversation::query()
            ->with(['user', 'assignedAdmin', 'messages'])
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return response()->json(ConversationResource::collection($conversations)->response()->getData(true));
    }

    #[OA\Get(
        path: '/admin/chat/conversations/{conversation}/messages',
        tags: ['Admin Chat'],
        summary: 'Get the full message history for a conversation',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Message history')],
    )]
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($request->user()->can('chat.manage'), 403);

        $conversation->load(['messages.sender', 'user']);
        $conversation->update(['admin_read_at' => now()]);

        return response()->json([
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'messages' => ChatMessageResource::collection($conversation->messages),
            ],
        ]);
    }

    #[OA\Post(
        path: '/admin/chat/conversations/{conversation}/messages',
        tags: ['Admin Chat'],
        summary: 'Reply to a customer in a conversation',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Message sent')],
    )]
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($request->user()->can('chat.manage'), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'admin_read_at' => now(),
            'assigned_admin_id' => $conversation->assigned_admin_id ?? $request->user()->id,
        ]);

        $message->load('sender');
        broadcast(new ChatMessageSent($message));

        return response()->json(['data' => new ChatMessageResource($message)], 201);
    }

    #[OA\Post(
        path: '/admin/chat/conversations/{conversation}/read',
        tags: ['Admin Chat'],
        summary: 'Mark a conversation as read',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Marked as read')],
    )]
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($request->user()->can('chat.manage'), 403);

        $conversation->update(['admin_read_at' => now()]);

        return response()->json(['data' => new ConversationResource($conversation)]);
    }
}
