<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransferService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  array{from_warehouse_id: int, to_warehouse_id: int, transfer_date: string, notes?: string, items: array<int, array{product_id: int, product_variant_id?: int|null, quantity: int}>}  $data
     */
    public function create(array $data, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            abort_if($data['from_warehouse_id'] === $data['to_warehouse_id'], 422, 'Source and destination warehouse must differ.');

            $fromWarehouse = Warehouse::query()->findOrFail($data['from_warehouse_id']);

            $transfer = StockTransfer::query()->create([
                'transfer_no' => 'TRF-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => 'pending',
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                ]);
            }

            // Stock leaves the source warehouse as soon as the transfer is dispatched.
            foreach ($transfer->items as $item) {
                $this->inventory->deduct(
                    product: $item->product,
                    variant: $item->variant,
                    warehouse: $fromWarehouse,
                    quantity: $item->quantity,
                    type: 'transfer_out',
                    reference: $transfer,
                    note: "Transfer {$transfer->transfer_no} dispatched",
                    userId: $userId,
                );
            }

            $transfer->update(['status' => 'in_transit']);

            return $transfer->fresh('items');
        });
    }

    /**
     * @param  array<int, array{item_id: int, received_quantity: int}>  $receivedItems
     */
    public function receive(StockTransfer $transfer, array $receivedItems, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedItems, $userId) {
            $toWarehouse = Warehouse::query()->findOrFail($transfer->to_warehouse_id);

            foreach ($receivedItems as $received) {
                $item = $transfer->items()->findOrFail($received['item_id']);
                $qty = (int) $received['received_quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $this->inventory->add(
                    product: $item->product,
                    variant: $item->variant,
                    warehouse: $toWarehouse,
                    quantity: $qty,
                    type: 'transfer_in',
                    reference: $transfer,
                    note: "Transfer {$transfer->transfer_no} received",
                    userId: $userId,
                );

                $item->increment('received_quantity', $qty);
            }

            $allReceived = $transfer->items()->get()->every(fn ($item) => $item->received_quantity >= $item->quantity);

            $transfer->update([
                'status' => $allReceived ? 'completed' : 'in_transit',
                'received_date' => $allReceived ? now()->toDateString() : $transfer->received_date,
            ]);

            return $transfer->fresh('items');
        });
    }
}
