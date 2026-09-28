<?php

use App\Models\PartyBundle;
use App\Models\PartyBundleItem;
use App\Models\Product;
use App\Models\User;

test('consumer can view party bundles on catalog page', function () {
    $vendor = User::factory()->vendor()->create();

    $product = Product::factory()->vendor($vendor)->create([
        'name' => 'Cold Trophy Lager',
        'selling_price' => 850.00,
        'stock_level' => 30,
        'is_active' => true,
    ]);

    $bundle = PartyBundle::create([
        'vendor_id' => $vendor->id,
        'title' => 'Gwallameji Party Pack',
        'slug' => 'gwallameji-party-pack',
        'description' => 'Great combo pack for student parties',
        'price' => 5000.00,
        'original_price' => 6000.00,
        'discount_percentage' => 17,
        'badge_text' => '🔥 SAVE 17%',
        'is_active' => true,
    ]);

    PartyBundleItem::create([
        'party_bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 6,
    ]);

    $response = $this->get(route('consumer.catalog'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Consumer/Catalog')
            ->has('partyBundles', 1)
        );
});

test('party bundle calculates items relationship properly', function () {
    $vendor = User::factory()->vendor()->create();
    $product = Product::factory()->vendor($vendor)->create();

    $bundle = PartyBundle::create([
        'vendor_id' => $vendor->id,
        'title' => 'Test Combo',
        'slug' => 'test-combo',
        'price' => 2000.00,
        'original_price' => 2500.00,
        'is_active' => true,
    ]);

    $item = PartyBundleItem::create([
        'party_bundle_id' => $bundle->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    expect($bundle->items)->toHaveCount(1)
        ->and($bundle->items->first()->product->id)->toBe($product->id);
});
