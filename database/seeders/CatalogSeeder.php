<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\BarcodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Electronics', 'Groceries', 'Fashion', 'Home & Living', 'Beauty & Health'])
            ->map(fn (string $name) => Category::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            ));

        $brands = collect(['Royal', 'Ceylon Goods', 'Lanka Prime', 'Island Essentials'])
            ->map(fn (string $name) => Brand::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true],
            ));

        $supplier = Supplier::query()->firstOrCreate(
            ['email' => 'supplier@wholesale.test'],
            [
                'name' => 'Wholesale Distributors (Pvt) Ltd',
                'company_name' => 'Wholesale Distributors',
                'phone' => '0112009000',
                'address' => 'Industrial Zone, Biyagama',
                'city' => 'Biyagama',
                'country' => 'Sri Lanka',
                'is_active' => true,
            ],
        );

        $mainWarehouse = Warehouse::query()->where('is_default', true)->first();
        $barcodes = app(BarcodeService::class);

        Product::factory()
            ->count(24)
            ->state(function () use ($categories, $brands, $supplier) {
                return [
                    'category_id' => $categories->random()->id,
                    'brand_id' => $brands->random()->id,
                    'supplier_id' => $supplier->id,
                ];
            })
            ->create()
            ->each(function (Product $product) use ($mainWarehouse) {
                $product->stocks()->create([
                    'warehouse_id' => $mainWarehouse->id,
                    'quantity' => fake()->numberBetween(20, 150),
                    'reserved_quantity' => 0,
                ]);
            });
    }
}
