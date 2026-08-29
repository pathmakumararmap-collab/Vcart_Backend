<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('path')->nullable();
            $table->dateTime('issued_at');
            $table->dateTime('due_at')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['issued', 'paid', 'void'])->default('issued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
