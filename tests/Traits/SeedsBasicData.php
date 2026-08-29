<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WarehouseSeeder;

trait SeedsBasicData
{
    protected function seedBasics(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(WarehouseSeeder::class);
        $this->seed(PaymentMethodSeeder::class);
    }

    protected function defaultWarehouse(): Warehouse
    {
        return Warehouse::query()->where('is_default', true)->firstOrFail();
    }

    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function managerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        return $user;
    }

    protected function cashierUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('pos_cashier');

        return $user;
    }

    protected function warehouseManagerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('warehouse_manager');

        return $user;
    }

    protected function customerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }
}
