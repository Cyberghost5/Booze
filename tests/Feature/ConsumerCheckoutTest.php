<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;

test('consumer can view public drinks catalog page', function () {
    $category = Category::factory()->create();
    Product::factory()->count(3)->create(['category_id' => $category->id, 'stock_level' => 10, 'is_active' => true]);

    $response = $this->get(route('consumer.catalog'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Consumer/Catalog')
            ->has('products', 3)
            ->has('categories', 1)
            ->where('deliveryFee', 500)
        );
});

test('checkout validates 18+ DOB gate and blocks underage DOB', function () {
    $consumer = User::factory()->consumer()->create();
    $product = Product::factory()->create(['stock_level' => 10, 'selling_price' => 1000.00]);

    // Underage DOB (e.g. 15 years old)
    $underageDob = Carbon::now()->subYears(15)->format('Y-m-d');

    $response = $this->actingAs($consumer)->post(route('consumer.checkout'), [
        'customer_name' => 'Teenager Student',
        'customer_phone' => '08011223344',
        'delivery_address' => 'Lodge 5, Gwallameji',
        'latitude' => 10.2847,
        'longitude' => 9.7915,
        'dob' => $underageDob,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertSessionHasErrors(['dob']);
    $this->assertDatabaseMissing('orders', ['customer_name' => 'Teenager Student']);
});

test('checkout creates pending order with fixed delivery fee and decrements stock', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $product1 = Product::factory()->vendor($vendor)->create([
        'name' => 'Trophy Lager 600ml',
        'selling_price' => 850.00,
        'stock_level' => 20,
    ]);

    $product2 = Product::factory()->vendor($vendor)->create([
        'name' => 'Guinness Extra Stout 600ml',
        'selling_price' => 1100.00,
        'stock_level' => 15,
    ]);

    $validDob = Carbon::now()->subYears(22)->format('Y-m-d');

    $response = $this->actingAs($consumer)->post(route('consumer.checkout'), [
        'customer_name' => 'Amina Bauchi',
        'customer_phone' => '09031704109',
        'delivery_address' => 'Executive Lodge 14, Gwallameji, Bauchi',
        'latitude' => 10.2847000,
        'longitude' => 9.7915000,
        'dob' => $validDob,
        'notes' => 'Deliver chilled please',
        'items' => [
            ['product_id' => $product1->id, 'quantity' => 2], // 1700
            ['product_id' => $product2->id, 'quantity' => 1], // 1100
        ],
    ]);

    // Subtotal: 1700 + 1100 = 2800. Delivery fee: 500. Total: 3300.
    $order = Order::where('customer_name', 'Amina Bauchi')->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('pending')
        ->and((float) $order->subtotal)->toBe(2800.0)
        ->and((float) $order->delivery_fee)->toBe(500.0)
        ->and((float) $order->total)->toBe(3300.0)
        ->and($order->items)->toHaveCount(2);

    $response->assertRedirect(route('consumer.orders.show', $order->id));

    // Verify stock levels decremented
    expect($product1->fresh()->stock_level)->toBe(18)
        ->and($product2->fresh()->stock_level)->toBe(14);
});

test('checkout fails when requested quantity exceeds available stock', function () {
    $consumer = User::factory()->consumer()->create();
    $product = Product::factory()->create(['stock_level' => 2, 'selling_price' => 1000.00]);
    $validDob = Carbon::now()->subYears(25)->format('Y-m-d');

    $response = $this->actingAs($consumer)->post(route('consumer.checkout'), [
        'customer_name' => 'Big Buyer',
        'customer_phone' => '08099887766',
        'delivery_address' => 'Lodge 1, Gwallameji',
        'latitude' => 10.2847,
        'longitude' => 9.7915,
        'dob' => $validDob,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 5], // Exceeds 2 stock
        ],
    ]);

    $response->assertSessionHasErrors(['cart']);
    $this->assertDatabaseMissing('orders', ['customer_name' => 'Big Buyer']);
    expect($product->fresh()->stock_level)->toBe(2);
});

test('consumer can view placed order status tracker page', function () {
    $user = User::factory()->create(['role' => 'consumer']);
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

    $response = $this->actingAs($user)->get(route('consumer.orders.show', $order->id));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Consumer/OrderShow')
            ->where('order.id', $order->id)
            ->where('order.status', 'pending')
        );
});
