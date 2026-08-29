<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class MultiChannelInventoryTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_website_facebook_and_pos_orders_all_deduct_the_same_central_inventory(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['is_active' => true]);

        Stock::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $cashMethod = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
        $customer = $this->customerUser();
        $admin = $this->adminUser();
        $cashier = $this->cashierUser();

        // Website order deducts 10
        $this->actingAs($customer, 'sanctum')->postJson('/api/v1/customer/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
            'customer_name' => 'Website Buyer',
        ])->assertCreated();

        $this->assertEquals(90, $this->currentQuantity($product->id, $warehouse->id));

        // Facebook order deducts 15
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/orders/facebook', [
            'channel_reference' => 'fb-123',
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 15]],
            'customer_name' => 'FB Buyer',
            'customer_phone' => '0770000000',
        ])->assertCreated();

        $this->assertEquals(75, $this->currentQuantity($product->id, $warehouse->id));

        // POS sale deducts 20
        $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/pos/checkout', [
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 20]],
            'payment_method_id' => $cashMethod->id,
        ])->assertOk();

        $this->assertEquals(55, $this->currentQuantity($product->id, $warehouse->id));

        // The ledger should show exactly 3 sale movements against the same stock row.
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity_change' => -10]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity_change' => -15]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity_change' => -20]);
    }

    public function test_pos_checkout_rejects_when_stock_is_insufficient(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create();

        Stock::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 3,
            'reserved_quantity' => 0,
        ]);

        $cashMethod = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
        $cashier = $this->cashierUser();

        $response = $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/pos/checkout', [
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
            'payment_method_id' => $cashMethod->id,
        ]);

        $response->assertUnprocessable();
        $this->assertEquals(3, $this->currentQuantity($product->id, $warehouse->id));
    }

    public function test_low_stock_alert_is_created_once_quantity_drops_to_or_below_threshold(): void
    {
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create(['low_stock_threshold' => 5]);

        Stock::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 8,
            'reserved_quantity' => 0,
        ]);

        $cashMethod = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
        $cashier = $this->cashierUser();

        $this->actingAs($cashier, 'sanctum')->postJson('/api/v1/pos/checkout', [
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
            'payment_method_id' => $cashMethod->id,
        ])->assertOk();

        $this->assertDatabaseHas('low_stock_alerts', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
            'current_quantity' => 4,
        ]);
    }

    private function currentQuantity(int $productId, int $warehouseId): int
    {
        return (int) Stock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity');
    }
}
