<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function paginateByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->role($role)->latest()->paginate($perPage);
    }
}
