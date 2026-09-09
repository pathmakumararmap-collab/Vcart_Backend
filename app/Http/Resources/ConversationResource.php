<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'assigned_admin_name' => $this->whenLoaded('assignedAdmin', fn () => $this->assignedAdmin?->name),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message_preview' => $this->whenLoaded(
                'messages',
                fn () => $this->messages->last()?->body,
            ),
            'unread_count' => $this->unreadCountForAdmin(),
            'customer_unread_count' => $this->unreadCountForCustomer(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
