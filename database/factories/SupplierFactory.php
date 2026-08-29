<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('07########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => 'Sri Lanka',
            'is_active' => true,
        ];
    }
}
