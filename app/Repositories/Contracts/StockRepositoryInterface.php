<?php

namespace App\Repositories\Contracts;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;

interface StockRepositoryInterface extends RepositoryInterface
{
    public function findOrCreateFor(int $productId, ?int $variantId, int $warehouseId): Stock;

    public function lowStock(?int $warehouseId = null): Collection;
}
