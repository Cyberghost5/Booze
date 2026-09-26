<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

test('users have default consumer role and helper methods work', function () {
    $consumer = User::factory()->create();

    expect($consumer->role)->toBe('consumer')
        ->and($consumer->isConsumer())->toBeTrue()
        ->and($consumer->isVendor())->toBeFalse();

    $vendor = User::factory()->vendor()->create();

    expect($vendor->role)->toBe('vendor_admin')
        ->and($vendor->isVendor())->toBeTrue()
        ->and($vendor->isConsumer())->toBeFalse()
        ->and($vendor->store_name)->not->toBeEmpty();
});

test('user relationships for consumer and vendor hold true', function () {
    $consumer = User::factory()->consumer()->create();
    $vendor = User::factory()->vendor()->create();

    $product = Product::factory()->vendor($vendor)->create();
    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
    ]);

    expect($consumer->orders)->toHaveCount(1)
        ->and($consumer->orders->first()->id)->toBe($order->id)
        ->and($vendor->vendorOrders)->toHaveCount(1)
        ->and($vendor->vendorOrders->first()->id)->toBe($order->id)
        ->and($vendor->vendorProducts)->toHaveCount(1)
        ->and($vendor->vendorProducts->first()->id)->toBe($product->id);
});
