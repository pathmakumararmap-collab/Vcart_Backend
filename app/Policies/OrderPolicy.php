<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view') || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create') || $user->isCustomer();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->can('orders.update') || $order->user_id === $user->id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }
}
