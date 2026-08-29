<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@royalsl.test'],
            [
                'name' => 'Royal SL Admin',
                'phone' => '0770000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['admin']);

        $manager = User::query()->firstOrCreate(
            ['email' => 'manager@royalsl.test'],
            [
                'name' => 'Store Manager',
                'phone' => '0770000002',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
        $manager->syncRoles(['manager']);

        $warehouseManager = User::query()->firstOrCreate(
            ['email' => 'warehouse@royalsl.test'],
            [
                'name' => 'Warehouse Manager',
                'phone' => '0770000003',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
        $warehouseManager->syncRoles(['warehouse_manager']);

        $cashier = User::query()->firstOrCreate(
            ['email' => 'cashier@royalsl.test'],
            [
                'name' => 'POS Cashier',
                'phone' => '0770000004',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
        $cashier->syncRoles(['pos_cashier']);

        $customer = User::query()->firstOrCreate(
            ['email' => 'customer@royalsl.test'],
            [
                'name' => 'Sample Customer',
                'phone' => '0770000005',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
        $customer->syncRoles(['customer']);
    }
}
