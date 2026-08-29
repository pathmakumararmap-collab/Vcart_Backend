<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE-####')),
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
            'applicable_to' => 'all',
        ];
    }
}
