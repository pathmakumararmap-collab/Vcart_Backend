<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_guest_can_browse_active_products(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_guest_can_view_a_single_product_by_slug(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->slug}");

        $response->assertOk()->assertJsonPath('data.id', $product->id);
    }

    public function test_inactive_product_is_not_publicly_visible(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->getJson("/api/v1/catalog/products/{$product->slug}")->assertNotFound();
    }

    public function test_products_can_be_searched_by_keyword(): void
    {
        Product::factory()->create(['name' => 'Wireless Bluetooth Headphones', 'is_active' => true]);
        Product::factory()->create(['name' => 'Stainless Steel Water Bottle', 'is_active' => true]);

        $response = $this->getJson('/api/v1/catalog/products?keyword=Bluetooth');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
