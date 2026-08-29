<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::query()->firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Central Warehouse',
                'type' => 'main',
                'address' => 'No. 10, Baseline Road, Colombo 09',
                'phone' => '0112345678',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        Warehouse::query()->firstOrCreate(
            ['code' => 'OUT-CMB01'],
            [
                'name' => 'Colombo City Outlet',
                'type' => 'outlet',
                'address' => 'No. 45, Galle Road, Colombo 03',
                'phone' => '0112233445',
                'is_default' => false,
                'is_active' => true,
            ],
        );

        Warehouse::query()->firstOrCreate(
            ['code' => 'OUT-KDY01'],
            [
                'name' => 'Kandy Outlet',
                'type' => 'outlet',
                'address' => 'No. 22, Peradeniya Road, Kandy',
                'phone' => '0812233445',
                'is_default' => false,
                'is_active' => true,
            ],
        );
    }
}
