<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 1000,
                'max_discount_amount' => 2000,
                'usage_limit' => null,
                'usage_limit_per_user' => 1,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'applicable_to' => 'all',
            ],
        );

        Coupon::query()->firstOrCreate(
            ['code' => 'FLAT500'],
            [
                'type' => 'fixed',
                'value' => 500,
                'min_order_amount' => 3000,
                'usage_limit' => 200,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
                'applicable_to' => 'all',
            ],
        );
    }
}
