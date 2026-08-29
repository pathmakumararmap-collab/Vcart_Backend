<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class ReportTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_sales_report_reflects_orders_from_multiple_channels(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['selling_price' => 1000]);
        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 100]);

        $cashMethod = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
        $cashier = $this->cashierUser();
        $admin = $this->adminUser();

        $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/pos/checkout', [
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method_id' => $cashMethod->id,
        ])->assertOk();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/reports/sales');

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.summary.total_orders'));
        $this->assertEquals('pos', $response->json('data.by_source.0.source'));
    }

    public function test_reports_require_permission(): void
    {
        $customer = $this->customerUser();

        $this->actingAs($customer, 'sanctum')->getJson('/api/v1/reports/sales')->assertForbidden();
    }

    public function test_stock_report_lists_low_stock_items(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['low_stock_threshold' => 10]);
        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/reports/stock');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('data.low_stock_count'));
    }
}
