<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->enum('source', ['website', 'facebook', 'pos'])->default('website');
            $table->string('channel_reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'packed', 'shipped',
                'delivered', 'completed', 'cancelled', 'returned',
            ])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('LKR');
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_email')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('served_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancelled_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
