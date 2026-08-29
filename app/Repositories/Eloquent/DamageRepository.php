<?php

namespace App\Repositories\Eloquent;

use App\Models\Damage;
use App\Repositories\Contracts\DamageRepositoryInterface;

class DamageRepository extends BaseRepository implements DamageRepositoryInterface
{
    public function __construct(Damage $model)
    {
        parent::__construct($model);
    }
}
