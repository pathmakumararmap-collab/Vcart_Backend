<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city().' Warehouse',
            'code' => 'WH-'.fake()->unique()->numerify('####'),
            'type' => 'branch',
            'address' => fake()->address(),
            'phone' => fake()->numerify('07########'),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
