<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
