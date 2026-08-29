<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('condition', ['good', 'damaged'])->default('good');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_return_items');
    }
};
