<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReturn;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockReturnService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array{order_id?: int|null, warehouse_id: int, type: string, reason?: string, notes?: string, items: array<int, array{product_id: int, product_variant_id?: int|null, quantity: int, condition: string}>}  $data
     */
    public function create(array $data, ?int $userId = null): StockReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);

            $return = StockReturn::query()->create([
                'return_no' => 'RET-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'order_id' => $data['order_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'type' => $data['type'],
                'status' => 'approved',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $variant = ! empty($item['product_variant_id'])
                    ? ProductVariant::query()->findOrFail($item['product_variant_id'])
                    : null;

                $return->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $item['quantity'],
                    'condition' => $item['condition'],
                ]);

                // Only goods returned in sellable condition go back into stock.
                if ($item['condition'] === 'good') {
                    $this->inventory->add(
                        product: $product,
                        variant: $variant,
                        warehouse: $warehouse,
                        quantity: $item['quantity'],
                        type: 'return',
                        reference: $return,
                        note: "Return {$return->return_no}",
                        userId: $userId,
                    );
                }
            }

            return $return->fresh('items');
        });
    }
}
