<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array{supplier_id: int, warehouse_id: int, order_date: string, expected_date?: string, notes?: string, items: array<int, array{product_id: int, product_variant_id?: int|null, quantity: int, unit_cost: float}>}  $data
     */
    public function create(array $data, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = collect($data['items']);
            $subtotal = $items->sum(fn (array $item) => $item['quantity'] * $item['unit_cost']);
            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $discountAmount = (float) ($data['discount_amount'] ?? 0);

            $purchase = Purchase::query()->create([
                'purchase_no' => 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'pending',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => round($subtotal + $taxAmount - $discountAmount, 2),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => round($item['quantity'] * $item['unit_cost'], 2),
                ]);
            }

            return $purchase->fresh('items');
        });
    }

    /**
     * Receiving a purchase is the moment stock actually increases — this is
     * what keeps "purchase order created" separate from "stock available".
     *
     * @param  array<int, array{item_id: int, received_quantity: int}>  $receivedItems
     */
    public function receive(Purchase $purchase, array $receivedItems, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $receivedItems, $userId) {
            $warehouse = Warehouse::query()->findOrFail($purchase->warehouse_id);

            foreach ($receivedItems as $received) {
                $item = $purchase->items()->findOrFail($received['item_id']);
                $qty = (int) $received['received_quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $this->inventory->add(
                    product: $item->product,
                    variant: $item->variant,
                    warehouse: $warehouse,
                    quantity: $qty,
                    type: 'purchase',
                    reference: $purchase,
                    note: "Purchase {$purchase->purchase_no} received",
                    userId: $userId,
                );

                $item->increment('received_quantity', $qty);
            }

            $allReceived = $purchase->items()->get()->every(fn ($item) => $item->received_quantity >= $item->quantity);

            $purchase->update([
                'status' => $allReceived ? 'received' : 'ordered',
                'received_date' => $allReceived ? now()->toDateString() : $purchase->received_date,
            ]);

            return $purchase->fresh('items');
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        $purchase->update(['status' => 'cancelled']);

        return $purchase->fresh();
    }
}
