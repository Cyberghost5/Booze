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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->enum('status', ['pending', 'packed', 'out_for_delivery', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('delivery_fee', 10, 2)->default(500.00); // Fixed delivery fee default
            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('delivery_address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
