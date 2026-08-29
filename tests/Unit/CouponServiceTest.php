<?php

namespace Tests\Unit;

use App\Exceptions\InvalidCouponException;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_discount_for_percentage_coupon(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'percentage', 'value' => 20, 'max_discount_amount' => null]);
        $service = app(CouponService::class);

        $discount = $service->calculateDiscount($coupon, 1000);

        $this->assertEquals(200.0, $discount);
    }

    public function test_calculate_discount_is_capped_by_max_discount_amount(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'percentage', 'value' => 50, 'max_discount_amount' => 100]);
        $service = app(CouponService::class);

        $discount = $service->calculateDiscount($coupon, 1000);

        $this->assertEquals(100.0, $discount);
    }

    public function test_fixed_discount_never_exceeds_the_subtotal(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'fixed', 'value' => 500]);
        $service = app(CouponService::class);

        $discount = $service->calculateDiscount($coupon, 300);

        $this->assertEquals(300.0, $discount);
    }

    public function test_validate_for_order_throws_when_below_minimum_order_amount(): void
    {
        $coupon = Coupon::factory()->create(['min_order_amount' => 5000]);
        $service = app(CouponService::class);

        $this->expectException(InvalidCouponException::class);

        $service->validateForOrder($coupon->code, 1000, null, new Collection);
    }

    public function test_validate_for_order_throws_for_unknown_code(): void
    {
        $service = app(CouponService::class);

        $this->expectException(InvalidCouponException::class);

        $service->validateForOrder('NON-EXISTENT', 1000, null, new Collection);
    }
}
