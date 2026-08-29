<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class PurchaseAndTransferTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_receiving_a_purchase_order_increases_warehouse_stock(): void
    {
        $manager = $this->warehouseManagerUser();
        $warehouse = $this->defaultWarehouse();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $createResponse = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/inventory/purchases', [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 50, 'unit_cost' => 100],
            ],
        ]);

        $createResponse->assertCreated();
        $purchaseId = $createResponse->json('data.id');
        $itemId = $createResponse->json('data.items.0.id');

        $this->assertDatabaseMissing('stocks', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id]);

        $receiveResponse = $this->actingAs($manager, 'sanctum')->postJson("/api/v1/inventory/purchases/{$purchaseId}/receive", [
            'items' => [
                ['item_id' => $itemId, 'received_quantity' => 50],
            ],
        ]);

        $receiveResponse->assertOk()->assertJsonPath('data.status', 'received');

        $this->assertDatabaseHas('stocks', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 50,
        ]);
    }

    public function test_stock_transfer_moves_quantity_between_warehouses(): void
    {
        $manager = $this->warehouseManagerUser();
        $fromWarehouse = $this->defaultWarehouse();
        $toWarehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $fromWarehouse->id, 'quantity' => 100]);

        $createResponse = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/inventory/stock-transfers', [
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id' => $toWarehouse->id,
            'transfer_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 30],
            ],
        ]);

        $createResponse->assertCreated()->assertJsonPath('data.status', 'in_transit');

        // Source warehouse is deducted immediately on dispatch.
        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'warehouse_id' => $fromWarehouse->id, 'quantity' => 70]);

        $transferId = $createResponse->json('data.id');
        $itemId = $createResponse->json('data.items.0.id');

        $receiveResponse = $this->actingAs($manager, 'sanctum')->postJson("/api/v1/inventory/stock-transfers/{$transferId}/receive", [
            'items' => [['item_id' => $itemId, 'received_quantity' => 30]],
        ]);

        $receiveResponse->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'warehouse_id' => $toWarehouse->id, 'quantity' => 30]);
    }

    public function test_damage_report_removes_stock_from_inventory(): void
    {
        $manager = $this->warehouseManagerUser();
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create();

        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 20]);

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/inventory/damages', [
            'warehouse_id' => $warehouse->id,
            'reason' => 'Water damage',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'estimated_loss' => 250],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 15]);
    }

    public function test_customer_return_in_good_condition_restocks_inventory(): void
    {
        $manager = $this->warehouseManagerUser();
        $warehouse = $this->defaultWarehouse();
        $product = Product::factory()->create();

        Stock::query()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10]);

        $response = $this->actingAs($manager, 'sanctum')->postJson('/api/v1/inventory/stock-returns', [
            'warehouse_id' => $warehouse->id,
            'type' => 'customer_return',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'condition' => 'good'],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('stocks', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 13]);
    }
}
