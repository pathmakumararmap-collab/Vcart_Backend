<?php

namespace App\Policies;

use App\Models\StockAdjustment;
use App\Models\User;

class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock-adjustments.view');
    }

    public function view(User $user, StockAdjustment $model): bool
    {
        return $user->can('stock-adjustments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock-adjustments.create');
    }

    public function update(User $user, StockAdjustment $model): bool
    {
        return $user->can('stock-adjustments.update');
    }

    public function delete(User $user, StockAdjustment $model): bool
    {
        return $user->can('stock-adjustments.delete');
    }
}
