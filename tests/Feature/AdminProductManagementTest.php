<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBasicData;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase, SeedsBasicData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBasics();
    }

    public function test_admin_can_create_a_product(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'cost_price' => 500,
            'selling_price' => 800,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('products', ['sku' => 'TEST-SKU-001', 'name' => 'Test Product']);
    }

    public function test_customer_cannot_create_a_product(): void
    {
        $customer = $this->customerUser();
        $category = Category::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => 'Should Fail',
            'sku' => 'FAIL-001',
            'cost_price' => 100,
            'selling_price' => 200,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_a_product(): void
    {
        $admin = $this->adminUser();
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/admin/products/{$product->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_admin_can_delete_a_product(): void
    {
        $admin = $this->adminUser();
        $product = Product::factory()->create();

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/admin/products/{$product->id}")->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
