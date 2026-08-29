<?php

namespace App\Repositories\Eloquent;

use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?Model
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }
}
