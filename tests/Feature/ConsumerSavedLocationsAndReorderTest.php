<?php

use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

test('consumer can store a new saved delivery location and set it as default', function () {
    $consumer = User::factory()->consumer()->create();

    $response = $this->actingAs($consumer)->post(route('consumer.delivery-locations.store'), [
        'label' => 'Bauchi Hostel Room 12',
        'address' => 'Room 12, Bauchi Student Hostel, Gwallameji',
        'landmark' => 'Near Water Tank',
        'latitude' => 10.2847000,
        'longitude' => 9.7915000,
        'is_default' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('delivery_locations', [
        'user_id' => $consumer->id,
        'label' => 'Bauchi Hostel Room 12',
        'is_default' => true,
    ]);

    expect($consumer->fresh()->default_address)->toBe('Room 12, Bauchi Student Hostel, Gwallameji');
});

test('consumer can toggle default delivery location', function () {
    $consumer = User::factory()->consumer()->create();

    $loc1 = DeliveryLocation::create([
        'user_id' => $consumer->id,
        'label' => 'Lodge A',
        'address' => 'Gwallameji Lodge A',
        'is_default' => true,
    ]);

    $loc2 = DeliveryLocation::create([
        'user_id' => $consumer->id,
        'label' => 'Lodge B',
        'address' => 'Gwallameji Lodge B',
        'is_default' => false,
    ]);

    $response = $this->actingAs($consumer)->patch(route('consumer.delivery-locations.set-default', $loc2->id));

    $response->assertRedirect();
    expect($loc1->fresh()->is_default)->toBeFalse()
        ->and($loc2->fresh()->is_default)->toBeTrue()
        ->and($consumer->fresh()->default_address)->toBe('Gwallameji Lodge B');
});

test('consumer can delete a saved delivery location', function () {
    $consumer = User::factory()->consumer()->create();

    $loc = DeliveryLocation::create([
        'user_id' => $consumer->id,
        'label' => 'Room 5',
        'address' => 'Address 5',
        'is_default' => false,
    ]);

    $response = $this->actingAs($consumer)->delete(route('consumer.delivery-locations.destroy', $loc->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('delivery_locations', [
        'id' => $loc->id,
    ]);
});

test('consumer can 1-tap reorder past order items into cart', function () {
    $consumer = User::factory()->consumer()->create();
    $vendor = User::factory()->vendor()->create();

    $product = Product::factory()->vendor($vendor)->create([
        'name' => 'Cold Trophy Lager',
        'selling_price' => 900.00,
        'stock_level' => 15,
        'is_active' => true,
    ]);

    $pastOrder = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'status' => 'delivered',
    ]);

    OrderItem::factory()->create([
        'order_id' => $pastOrder->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'unit_price' => 900.00,
        'subtotal' => 1800.00,
    ]);

    $response = $this->actingAs($consumer)->post(route('consumer.orders.reorder', $pastOrder->id));

    $response->assertRedirect(route('consumer.catalog'))
        ->assertSessionHas('reorderCart')
        ->assertSessionHas('success');

    $reorderCart = session('reorderCart');
    expect($reorderCart)->toHaveCount(1)
        ->and($reorderCart[0]['product_id'])->toBe($product->id)
        ->and($reorderCart[0]['quantity'])->toBe(2);
});
