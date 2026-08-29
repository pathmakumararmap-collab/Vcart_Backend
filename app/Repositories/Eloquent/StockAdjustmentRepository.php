<?php

namespace App\Repositories\Eloquent;

use App\Models\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;

class StockAdjustmentRepository extends BaseRepository implements StockAdjustmentRepositoryInterface
{
    public function __construct(StockAdjustment $model)
    {
        parent::__construct($model);
    }
}
