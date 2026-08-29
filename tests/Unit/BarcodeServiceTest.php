<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_a_valid_svg_barcode(): void
    {
        $service = app(BarcodeService::class);

        $svg = $service->generateSvg('1234567890');

        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_generates_a_unique_barcode_that_does_not_collide_with_existing_products(): void
    {
        $existing = Product::factory()->create(['barcode' => 'DUPLICATE001']);
        $service = app(BarcodeService::class);

        $code = $service->generateUniqueCode();

        $this->assertNotEquals($existing->barcode, $code);
        $this->assertDatabaseMissing('products', ['barcode' => $code]);
    }
}
