<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function tree(): Collection;
}
