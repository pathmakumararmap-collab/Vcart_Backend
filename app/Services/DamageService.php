<?php

namespace App\Services;

use App\Models\Damage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DamageService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array{warehouse_id: int, reason?: string, notes?: string, items: array<int, array{product_id: int, product_variant_id?: int|null, quantity: int, estimated_loss?: float}>}  $data
     */
    public function create(array $data, ?int $userId = null): Damage
    {
        return DB::transaction(function () use ($data, $userId) {
            $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);

            $damage = Damage::query()->create([
                'damage_no' => 'DMG-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'warehouse_id' => $warehouse->id,
                'reason' => $data['reason'] ?? null,
                'status' => 'approved',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $variant = ! empty($item['product_variant_id'])
                    ? ProductVariant::query()->findOrFail($item['product_variant_id'])
                    : null;

                $damage->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $item['quantity'],
                    'estimated_loss' => $item['estimated_loss'] ?? 0,
                ]);

                $this->inventory->deduct(
                    product: $product,
                    variant: $variant,
                    warehouse: $warehouse,
                    quantity: $item['quantity'],
                    type: 'damage',
                    reference: $damage,
                    note: "Damage {$damage->damage_no}",
                    userId: $userId,
                );
            }

            return $damage->fresh('items');
        });
    }
}
