<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function all(array $relations = []): Collection;

    public function paginate(int $perPage = 15, array $relations = [], array $filters = []): LengthAwarePaginator;

    public function find(int $id, array $relations = []): ?Model;

    public function findOrFail(int $id, array $relations = []): Model;

    public function create(array $data): Model;

    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;
}
