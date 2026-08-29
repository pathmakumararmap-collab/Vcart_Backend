<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAdjustmentService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array{warehouse_id: int, reason: string, notes?: string, items: array<int, array{product_id: int, product_variant_id?: int|null, new_quantity: int}>}  $data
     */
    public function create(array $data, ?int $userId = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);

            $adjustment = StockAdjustment::query()->create([
                'adjustment_no' => 'ADJ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'warehouse_id' => $warehouse->id,
                'reason' => $data['reason'],
                'status' => 'approved',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $variant = ! empty($item['product_variant_id'])
                    ? ProductVariant::query()->findOrFail($item['product_variant_id'])
                    : null;

                $movement = $this->inventory->setQuantity(
                    product: $product,
                    variant: $variant,
                    warehouse: $warehouse,
                    newQuantity: (int) $item['new_quantity'],
                    reference: $adjustment,
                    note: "Adjustment {$adjustment->adjustment_no}",
                    userId: $userId,
                );

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity_change' => $movement->quantity_change,
                    'before_quantity' => $movement->quantity_before,
                    'after_quantity' => $movement->quantity_after,
                ]);
            }

            return $adjustment->fresh('items');
        });
    }
}
