<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?Model;

    public function paginateByRole(string $role, int $perPage = 15): LengthAwarePaginator;
}
