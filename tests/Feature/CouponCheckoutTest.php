<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_percentage_coupon_discounts_the_order_total(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['selling_price' => 1000, 'discount_price' => null]);

        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 50]);

        Coupon::query()->create([
            'code' => 'TEST10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 0,
            'is_active' => true,
            'applicable_to' => 'all',
        ]);

        $customer = $this->customerUser();

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/customer/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'customer_name' => 'Coupon Buyer',
            'coupon_code' => 'TEST10',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.subtotal', 2000);
        $response->assertJsonPath('data.discount_amount', 200);
        $response->assertJsonPath('data.total_amount', 1800);
    }

    public function test_invalid_coupon_code_is_rejected(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['selling_price' => 500]);

        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 50]);

        $customer = $this->customerUser();

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/customer/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Buyer',
            'coupon_code' => 'DOES-NOT-EXIST',
        ]);

        $response->assertUnprocessable();
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['selling_price' => 500]);

        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 50]);

        Coupon::query()->create([
            'code' => 'EXPIRED1',
            'type' => 'fixed',
            'value' => 50,
            'is_active' => true,
            'applicable_to' => 'all',
            'expires_at' => now()->subDay(),
        ]);

        $customer = $this->customerUser();

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/customer/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Buyer',
            'coupon_code' => 'EXPIRED1',
        ]);

        $response->assertUnprocessable();
    }
}
