<?php

namespace App\Repositories\Eloquent;

use App\Models\StockTransfer;
use App\Repositories\Contracts\StockTransferRepositoryInterface;

class StockTransferRepository extends BaseRepository implements StockTransferRepositoryInterface
{
    public function __construct(StockTransfer $model)
    {
        parent::__construct($model);
    }
}
