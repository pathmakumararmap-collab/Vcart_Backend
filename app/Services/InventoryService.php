<?php

namespace App\Services;

use App\Events\LowStockDetected;
use App\Exceptions\InsufficientStockException;
use App\Models\LowStockAlert;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for every stock mutation in the system.
 *
 * Website checkout, Facebook order import, and outlet POS sales all funnel
 * through deduct(); purchases, transfers, adjustments, returns, and damages
 * all funnel through add()/adjust(). No other code path is allowed to write
 * to the `stocks` table, guaranteeing every channel shares one inventory.
 */
class InventoryService
{
    public function __construct(private readonly StockRepositoryInterface $stocks) {}

    public function availableQuantity(Product $product, ?ProductVariant $variant, Warehouse $warehouse): int
    {
        $stock = $this->stocks->findOrCreateFor($product->id, $variant?->id, $warehouse->id);

        return $stock->availableQuantity();
    }

    public function hasSufficientStock(Product $product, ?ProductVariant $variant, Warehouse $warehouse, int $quantity): bool
    {
        return $this->availableQuantity($product, $variant, $warehouse) >= $quantity;
    }

    /**
     * Reduce stock — used for every sale, regardless of channel (website, facebook, pos),
     * and for damages.
     */
    public function deduct(
        Product $product,
        ?ProductVariant $variant,
        Warehouse $warehouse,
        int $quantity,
        string $type = 'sale',
        ?Model $reference = null,
        ?string $note = null,
        ?int $userId = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $variant, $warehouse, $quantity, $type, $reference, $note, $userId) {
            $stock = $this->lockedStock($product->id, $variant?->id, $warehouse->id);

            if ($stock->availableQuantity() < $quantity) {
                throw new InsufficientStockException($product->name, $quantity, $stock->availableQuantity());
            }

            $before = $stock->quantity;
            $stock->quantity -= $quantity;
            $stock->save();

            $movement = $this->recordMovement($product, $variant, $warehouse, $type, -$quantity, $before, $stock->quantity, $reference, $note, $userId);

            $this->evaluateLowStock($stock, $product);

            return $movement;
        });
    }

    /**
     * Increase stock — used for purchases, stock transfers in, good customer returns,
     * and positive adjustments.
     */
    public function add(
        Product $product,
        ?ProductVariant $variant,
        Warehouse $warehouse,
        int $quantity,
        string $type = 'purchase',
        ?Model $reference = null,
        ?string $note = null,
        ?int $userId = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $variant, $warehouse, $quantity, $type, $reference, $note, $userId) {
            $stock = $this->lockedStock($product->id, $variant?->id, $warehouse->id);

            $before = $stock->quantity;
            $stock->quantity += $quantity;
            $stock->save();

            $movement = $this->recordMovement($product, $variant, $warehouse, $type, $quantity, $before, $stock->quantity, $reference, $note, $userId);

            $this->evaluateLowStock($stock, $product);

            return $movement;
        });
    }

    /**
     * Set stock to an exact quantity (physical stock count adjustment).
     */
    public function setQuantity(
        Product $product,
        ?ProductVariant $variant,
        Warehouse $warehouse,
        int $newQuantity,
        ?Model $reference = null,
        ?string $note = null,
        ?int $userId = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $variant, $warehouse, $newQuantity, $reference, $note, $userId) {
            $stock = $this->lockedStock($product->id, $variant?->id, $warehouse->id);

            $before = $stock->quantity;
            $delta = $newQuantity - $before;
            $stock->quantity = $newQuantity;
            $stock->save();

            $movement = $this->recordMovement($product, $variant, $warehouse, 'adjustment', $delta, $before, $stock->quantity, $reference, $note, $userId);

            $this->evaluateLowStock($stock, $product);

            return $movement;
        });
    }

    private function lockedStock(int $productId, ?int $variantId, int $warehouseId): Stock
    {
        /** @var Stock $stock */
        $stock = Stock::query()
            ->lockForUpdate()
            ->firstOrCreate([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
            ], [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]);

        return $stock;
    }

    private function recordMovement(
        Product $product,
        ?ProductVariant $variant,
        Warehouse $warehouse,
        string $type,
        int $change,
        int $before,
        int $after,
        ?Model $reference,
        ?string $note,
        ?int $userId,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'warehouse_id' => $warehouse->id,
            'type' => $type,
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    private function evaluateLowStock(Stock $stock, Product $product): void
    {
        $threshold = $product->low_stock_threshold;

        $alert = LowStockAlert::query()->firstOrNew([
            'product_id' => $stock->product_id,
            'product_variant_id' => $stock->product_variant_id,
            'warehouse_id' => $stock->warehouse_id,
        ]);

        if ($stock->quantity <= $threshold) {
            $wasAlreadyActive = $alert->exists && $alert->status === 'active';

            $alert->threshold = $threshold;
            $alert->current_quantity = $stock->quantity;
            $alert->status = 'active';
            $alert->notified_at = $wasAlreadyActive ? $alert->notified_at : now();
            $alert->save();

            if (! $wasAlreadyActive) {
                event(new LowStockDetected($alert->fresh(['product', 'variant', 'warehouse'])));
            }

            return;
        }

        if ($alert->exists && $alert->status === 'active') {
            $alert->status = 'resolved';
            $alert->current_quantity = $stock->quantity;
            $alert->save();
        }
    }
}
