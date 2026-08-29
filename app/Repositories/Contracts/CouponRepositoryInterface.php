<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CouponRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?Model;
}
