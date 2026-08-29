<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findBySku(string $sku): ?Model;

    public function findByBarcode(string $barcode): ?Model;

    public function findBySlug(string $slug): ?Model;

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;
}
