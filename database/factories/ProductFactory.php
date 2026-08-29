<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $cost = fake()->randomFloat(2, 100, 5000);

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'supplier_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'barcode' => fake()->unique()->numerify('###########'),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'cost_price' => $cost,
            'selling_price' => round($cost * 1.4, 2),
            'discount_price' => null,
            'tax_rate' => 0,
            'unit' => 'pcs',
            'has_variants' => false,
            'is_active' => true,
            'is_featured' => false,
            'low_stock_threshold' => 5,
            'weight' => fake()->randomFloat(2, 0.1, 10),
        ];
    }
}
