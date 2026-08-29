<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'products.view', 'products.create', 'products.update', 'products.delete', 'products.viewCost',
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'brands.view', 'brands.create', 'brands.update', 'brands.delete',
        'coupons.view', 'coupons.create', 'coupons.update', 'coupons.delete',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'orders.view', 'orders.create', 'orders.update', 'orders.delete',
        'warehouses.view', 'warehouses.create', 'warehouses.update', 'warehouses.delete',
        'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
        'purchases.view', 'purchases.create', 'purchases.update', 'purchases.receive',
        'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.update', 'stock-transfers.receive',
        'stock-adjustments.view', 'stock-adjustments.create', 'stock-adjustments.update',
        'stock-returns.view', 'stock-returns.create', 'stock-returns.update',
        'damages.view', 'damages.create', 'damages.update',
        'stock.view',
        'payments.view', 'payments.create', 'payments.update',
        'pos.sell',
        'reports.view', 'reports.manage',
        'reviews.moderate',
        'activity-logs.view',
    ];

    public function run(): void
    {
        Cache::forget(config('permission.cache.key'));

        foreach ($this->permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->syncPermissions($this->permissions);

        $manager = Role::query()->firstOrCreate(['name' => 'manager', 'guard_name' => 'sanctum']);
        $manager->syncPermissions(array_values(array_filter($this->permissions, fn ($p) => ! str_starts_with($p, 'users.'))));

        $warehouseManager = Role::query()->firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'sanctum']);
        $warehouseManager->syncPermissions([
            'products.view', 'stock.view', 'warehouses.view', 'warehouses.update',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'purchases.view', 'purchases.create', 'purchases.update', 'purchases.receive',
            'stock-transfers.view', 'stock-transfers.create', 'stock-transfers.update', 'stock-transfers.receive',
            'stock-adjustments.view', 'stock-adjustments.create', 'stock-adjustments.update',
            'stock-returns.view', 'stock-returns.create', 'stock-returns.update',
            'damages.view', 'damages.create', 'damages.update',
            'reports.view',
        ]);

        $posCashier = Role::query()->firstOrCreate(['name' => 'pos_cashier', 'guard_name' => 'sanctum']);
        $posCashier->syncPermissions([
            'products.view', 'stock.view', 'pos.sell', 'orders.view', 'orders.create',
            'payments.view', 'payments.create',
        ]);

        Role::query()->firstOrCreate(['name' => 'customer', 'guard_name' => 'sanctum']);
    }
}
