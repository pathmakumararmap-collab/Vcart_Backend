<?php

namespace App\Policies;

use App\Models\Damage;
use App\Models\User;

class DamagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('damages.view');
    }

    public function view(User $user, Damage $model): bool
    {
        return $user->can('damages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('damages.create');
    }

    public function update(User $user, Damage $model): bool
    {
        return $user->can('damages.update');
    }

    public function delete(User $user, Damage $model): bool
    {
        return $user->can('damages.delete');
    }
}
