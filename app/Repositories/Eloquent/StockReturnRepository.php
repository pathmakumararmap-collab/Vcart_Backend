<?php

namespace App\Repositories\Eloquent;

use App\Models\StockReturn;
use App\Repositories\Contracts\StockReturnRepositoryInterface;

class StockReturnRepository extends BaseRepository implements StockReturnRepositoryInterface
{
    public function __construct(StockReturn $model)
    {
        parent::__construct($model);
    }
}
