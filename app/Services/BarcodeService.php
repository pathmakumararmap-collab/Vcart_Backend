<?php

namespace App\Services;

use App\Models\Product;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    public function generateSvg(string $code): string
    {
        $generator = new BarcodeGeneratorSVG;

        return $generator->getBarcode($code, $generator::TYPE_CODE_128);
    }

    public function generatePng(string $code): string
    {
        $generator = new BarcodeGeneratorPNG;

        return $generator->getBarcode($code, $generator::TYPE_CODE_128);
    }

    public function generateForProduct(Product $product, string $format = 'svg'): string
    {
        return $format === 'png'
            ? $this->generatePng($product->barcode)
            : $this->generateSvg($product->barcode);
    }

    public function generateUniqueCode(string $prefix = ''): string
    {
        do {
            $code = $prefix.strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
        } while (Product::query()->where('barcode', $code)->exists());

        return $code;
    }
}
