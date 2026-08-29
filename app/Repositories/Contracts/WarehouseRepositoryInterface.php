<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function default(): ?Model;
}
