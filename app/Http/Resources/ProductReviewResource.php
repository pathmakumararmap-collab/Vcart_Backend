<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'reviewer_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'rating' => (int) $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'status' => $this->status,
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => Storage::disk('public')->url($image->path),
            ])),
            'is_mine' => $this->when(
                $request->user() !== null,
                fn () => $this->user_id === $request->user()->id
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
