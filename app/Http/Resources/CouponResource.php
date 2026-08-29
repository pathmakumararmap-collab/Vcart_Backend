<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => (float) $this->value,
            'min_order_amount' => (float) $this->min_order_amount,
            'max_discount_amount' => $this->max_discount_amount ? (float) $this->max_discount_amount : null,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'used_count' => $this->used_count,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_valid' => $this->isValid(),
            'applicable_to' => $this->applicable_to,
        ];
    }
}
