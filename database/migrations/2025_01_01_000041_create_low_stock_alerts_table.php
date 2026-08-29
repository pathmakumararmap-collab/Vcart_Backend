<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('threshold');
            $table->integer('current_quantity');
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'warehouse_id'], 'low_stock_alerts_product_variant_warehouse_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('low_stock_alerts');
    }
};
