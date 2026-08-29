<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'Credit / Debit Card', 'code' => 'card'],
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer'],
            ['name' => 'Cash on Delivery', 'code' => 'cod'],
            ['name' => 'Online Payment Gateway', 'code' => 'online_gateway'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->firstOrCreate(
                ['code' => $method['code']],
                ['name' => $method['name'], 'is_active' => true],
            );
        }
    }
}
