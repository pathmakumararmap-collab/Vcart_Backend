<?php

namespace App\Repositories\Eloquent;

use App\Models\Purchase;
use App\Repositories\Contracts\PurchaseRepositoryInterface;

class PurchaseRepository extends BaseRepository implements PurchaseRepositoryInterface
{
    public function __construct(Purchase $model)
    {
        parent::__construct($model);
    }
}
