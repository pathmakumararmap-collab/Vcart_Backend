<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deduct_reduces_quantity_and_records_a_movement(): void
    {
        $product = Product::factory()->create(['low_stock_threshold' => 5]);
        $warehouse = Warehouse::factory()->create();
        $service = app(InventoryService::class);

        $service->add($product, null, $warehouse, 50, 'initial');
        $movement = $service->deduct($product, null, $warehouse, 20, 'sale');

        $this->assertEquals(30, $movement->quantity_after);
        $this->assertEquals(-20, $movement->quantity_change);
        $this->assertEquals(30, $service->availableQuantity($product, null, $warehouse));
    }

    public function test_deduct_throws_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $service = app(InventoryService::class);

        $service->add($product, null, $warehouse, 5, 'initial');

        $this->expectException(InsufficientStockException::class);

        $service->deduct($product, null, $warehouse, 10, 'sale');
    }

    public function test_set_quantity_creates_an_adjustment_movement_with_correct_delta(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $service = app(InventoryService::class);

        $service->add($product, null, $warehouse, 40, 'initial');
        $movement = $service->setQuantity($product, null, $warehouse, 25);

        $this->assertEquals('adjustment', $movement->type);
        $this->assertEquals(-15, $movement->quantity_change);
        $this->assertEquals(25, $service->availableQuantity($product, null, $warehouse));
    }

    public function test_low_stock_alert_is_created_and_resolved_correctly(): void
    {
        $product = Product::factory()->create(['low_stock_threshold' => 10]);
        $warehouse = Warehouse::factory()->create();
        $service = app(InventoryService::class);

        $service->add($product, null, $warehouse, 20, 'initial');
        $service->deduct($product, null, $warehouse, 15, 'sale');

        $this->assertDatabaseHas('low_stock_alerts', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'active',
            'current_quantity' => 5,
        ]);

        $service->add($product, null, $warehouse, 20, 'purchase');

        $this->assertDatabaseHas('low_stock_alerts', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'resolved',
        ]);
    }
}
