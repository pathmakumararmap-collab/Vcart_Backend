<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;

class PaymentMethodRepository extends BaseRepository implements PaymentMethodRepositoryInterface
{
    public function __construct(PaymentMethod $model)
    {
        parent::__construct($model);
    }
}
