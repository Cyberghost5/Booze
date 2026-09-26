<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('unit')->default('bottle'); // bottle, can, crate, pack, etc.
            $table->decimal('cost_price', 10, 2)->default(0.00); // COGS
            $table->decimal('selling_price', 10, 2)->default(0.00); // Retail price
            $table->integer('stock_level')->default(0);
            $table->boolean('is_global')->default(false); // Global catalog vs vendor custom item
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
