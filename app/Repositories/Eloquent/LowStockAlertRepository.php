<?php

namespace App\Repositories\Eloquent;

use App\Models\LowStockAlert;
use App\Repositories\Contracts\LowStockAlertRepositoryInterface;

class LowStockAlertRepository extends BaseRepository implements LowStockAlertRepositoryInterface
{
    public function __construct(LowStockAlert $model)
    {
        parent::__construct($model);
    }
}
