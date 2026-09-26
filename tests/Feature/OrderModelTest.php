<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

test('order can be created with items and calculates correct totals', function () {
    $consumer = User::factory()->consumer()->create();
    $vendor = User::factory()->vendor()->create();

    $product1 = Product::factory()->create(['selling_price' => 1000.00]);
    $product2 = Product::factory()->create(['selling_price' => 2500.00]);

    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'subtotal' => 4500.00, // (2 * 1000) + (1 * 2500)
        'delivery_fee' => 500.00,
        'total' => 5000.00,
        'status' => 'pending',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'product_name' => $product1->name,
        'quantity' => 2,
        'unit_price' => 1000.00,
        'subtotal' => 2000.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'product_name' => $product2->name,
        'quantity' => 1,
        'unit_price' => 2500.00,
        'subtotal' => 2500.00,
    ]);

    expect($order->items)->toHaveCount(2)
        ->and((float) $order->subtotal)->toBe(4500.0)
        ->and((float) $order->delivery_fee)->toBe(500.0)
        ->and((float) $order->total)->toBe(5000.0)
        ->and($order->status)->toBe('pending');
});
