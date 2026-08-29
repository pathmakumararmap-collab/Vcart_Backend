<?php

namespace App\Repositories\Eloquent;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    public function default(): ?Model
    {
        return $this->model->newQuery()->where('is_default', true)->first();
    }
}
