<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('unauthenticated users and consumers cannot access vendor inventory page', function () {
    $response = $this->get(route('vendor.inventory.index'));
    $response->assertRedirect(route('login'));

    $consumer = User::factory()->consumer()->create();
    $this->actingAs($consumer)
        ->get(route('vendor.inventory.index'))
        ->assertStatus(403);
});

test('vendor can view inventory page and browse global catalog', function () {
    $vendor = User::factory()->vendor()->create();
    $category = Category::factory()->create();
    Product::factory()->count(3)->global()->create(['category_id' => $category->id]);
    Product::factory()->count(2)->vendor($vendor)->create(['category_id' => $category->id]);

    $response = $this->actingAs($vendor)->get(route('vendor.inventory.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendor/Inventory/Index')
            ->has('myStock', 2)
            ->has('globalCatalog', 3)
            ->has('metrics')
        );
});

test('vendor can stock item from global catalog in one click', function () {
    $vendor = User::factory()->vendor()->create();
    $globalItem = Product::factory()->global()->create([
        'name' => 'Trophy Lager 600ml',
        'cost_price' => 650.00,
        'selling_price' => 850.00,
    ]);

    $response = $this->actingAs($vendor)->post(route('vendor.inventory.store-global'), [
        'global_product_id' => $globalItem->id,
        'stock_level' => 48,
        'cost_price' => 650.00,
        'selling_price' => 850.00,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', [
        'vendor_id' => $vendor->id,
        'name' => 'Trophy Lager 600ml',
        'stock_level' => 48,
        'is_global' => false,
    ]);
});

test('vendor can create custom product with COGS and stock count', function () {
    $vendor = User::factory()->vendor()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($vendor)->post(route('vendor.inventory.store-custom'), [
        'name' => 'Gwallameji Local Brew',
        'category_id' => $category->id,
        'unit' => 'bottle',
        'cost_price' => 400.00,
        'selling_price' => 600.00,
        'stock_level' => 20,
        'description' => 'Fresh local drink',
        'image_url' => 'https://images.unsplash.com/photo-1527281400683-1aae777175f8',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', [
        'vendor_id' => $vendor->id,
        'name' => 'Gwallameji Local Brew',
        'cost_price' => 400.00,
        'selling_price' => 600.00,
        'stock_level' => 20,
    ]);
});

test('vendor can increment or decrement stock level', function () {
    $vendor = User::factory()->vendor()->create();
    $product = Product::factory()->vendor($vendor)->create(['stock_level' => 10]);

    $response = $this->actingAs($vendor)->patch(route('vendor.inventory.update-stock', $product->id), [
        'stock_level' => 15,
    ]);

    $response->assertRedirect();
    expect($product->fresh()->stock_level)->toBe(15);
});

test('vendor cannot edit or delete another vendors inventory item', function () {
    $vendor1 = User::factory()->vendor()->create();
    $vendor2 = User::factory()->vendor()->create();

    $productVendor1 = Product::factory()->vendor($vendor1)->create();

    $this->actingAs($vendor2)
        ->patch(route('vendor.inventory.update-stock', $productVendor1->id), ['stock_level' => 99])
        ->assertStatus(403);

    $this->actingAs($vendor2)
        ->delete(route('vendor.inventory.destroy', $productVendor1->id))
        ->assertStatus(403);
});
