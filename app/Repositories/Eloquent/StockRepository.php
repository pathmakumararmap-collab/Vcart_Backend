<?php

namespace App\Repositories\Eloquent;

use App\Models\Stock;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StockRepository extends BaseRepository implements StockRepositoryInterface
{
    public function __construct(Stock $model)
    {
        parent::__construct($model);
    }

    public function findOrCreateFor(int $productId, ?int $variantId, int $warehouseId): Stock
    {
        return $this->model->newQuery()->firstOrCreate([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
        ], [
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    public function lowStock(?int $warehouseId = null): Collection
    {
        $query = $this->model->newQuery()
            ->with(['product', 'variant', 'warehouse'])
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->whereColumn('stocks.quantity', '<=', 'products.low_stock_threshold')
            ->select('stocks.*');

        if ($warehouseId) {
            $query->where('stocks.warehouse_id', $warehouseId);
        }

        return $query->get();
    }
}
