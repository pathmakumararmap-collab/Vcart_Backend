<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->path ? Storage::disk('public')->url($this->path) : null,
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }
}
