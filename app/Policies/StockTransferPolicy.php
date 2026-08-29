<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock-transfers.view');
    }

    public function view(User $user, StockTransfer $model): bool
    {
        return $user->can('stock-transfers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock-transfers.create');
    }

    public function update(User $user, StockTransfer $model): bool
    {
        return $user->can('stock-transfers.update');
    }

    public function delete(User $user, StockTransfer $model): bool
    {
        return $user->can('stock-transfers.delete');
    }
}
