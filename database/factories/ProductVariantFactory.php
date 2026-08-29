<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'VAR-'.fake()->unique()->numerify('######'),
            'barcode' => fake()->unique()->numerify('###########'),
            'attributes' => ['color' => fake()->safeColorName(), 'size' => fake()->randomElement(['S', 'M', 'L', 'XL'])],
            'cost_price' => fake()->randomFloat(2, 100, 5000),
            'selling_price' => fake()->randomFloat(2, 200, 7000),
            'is_active' => true,
        ];
    }
}
