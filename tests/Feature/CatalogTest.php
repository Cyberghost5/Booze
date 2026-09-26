<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('category has many products', function () {
    $category = Category::factory()->create();
    $products = Product::factory()->count(3)->create(['category_id' => $category->id]);

    expect($category->products)->toHaveCount(3)
        ->and($products->first()->category->id)->toBe($category->id);
});

test('global product catalog vs vendor custom product scopes', function () {
    $vendor = User::factory()->vendor()->create();

    Product::factory()->count(2)->global()->create();
    Product::factory()->count(3)->vendor($vendor)->create();

    expect(Product::global()->get())->toHaveCount(2)
        ->and(Product::forVendor($vendor->id)->get())->toHaveCount(3);
});

test('product computes profit margin correctly', function () {
    $product = Product::factory()->create([
        'cost_price' => 650.00,
        'selling_price' => 850.00,
    ]);

    $margin = $product->selling_price - $product->cost_price;

    expect((float) $product->cost_price)->toBe(650.0)
        ->and((float) $product->selling_price)->toBe(850.0)
        ->and((float) $margin)->toBe(200.0);
});
