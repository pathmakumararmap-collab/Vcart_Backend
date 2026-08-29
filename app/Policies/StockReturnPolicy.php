<?php

namespace App\Policies;

use App\Models\StockReturn;
use App\Models\User;

class StockReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock-returns.view');
    }

    public function view(User $user, StockReturn $model): bool
    {
        return $user->can('stock-returns.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock-returns.create');
    }

    public function update(User $user, StockReturn $model): bool
    {
        return $user->can('stock-returns.update');
    }

    public function delete(User $user, StockReturn $model): bool
    {
        return $user->can('stock-returns.delete');
    }
}
