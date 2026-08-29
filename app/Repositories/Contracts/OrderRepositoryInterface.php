<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function findByOrderNo(string $orderNo): ?Model;

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;
}
