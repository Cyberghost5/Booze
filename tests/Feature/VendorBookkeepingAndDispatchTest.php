<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\WhatsAppDispatchService;

test('unauthenticated users and consumers cannot access vendor orders page', function () {
    $this->get(route('vendor.orders.index'))
        ->assertRedirect(route('login'));

    $consumer = User::factory()->consumer()->create();
    $this->actingAs($consumer)
        ->get(route('vendor.orders.index'))
        ->assertStatus(403);
});

test('vendor can view orders page and ledger calculates sales COGS and net profit correctly', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $product1 = Product::factory()->vendor($vendor)->create([
        'cost_price' => 600.00,
        'selling_price' => 1000.00,
    ]);

    $product2 = Product::factory()->vendor($vendor)->create([
        'cost_price' => 1500.00,
        'selling_price' => 2500.00,
    ]);

    // Order 1: 2x product1 (Selling 2000, COGS 1200, Profit 800)
    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'subtotal' => 2000.00,
        'delivery_fee' => 500.00,
        'total' => 2500.00,
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

    $response = $this->actingAs($vendor)->get(route('vendor.orders.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendor/Orders/Index')
            ->has('orders', 1)
            ->where('ledger.totalSales', 2000)
            ->where('ledger.totalCogs', 1200)
            ->where('ledger.netProfit', 800)
            ->where('ledger.profitMargin', 40)
        );
});

test('vendor can update order status sequentially', function () {
    $vendor = User::factory()->vendor()->create();
    $order = Order::factory()->create(['status' => 'pending', 'vendor_id' => null]);

    $response = $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), [
        'status' => 'packed',
    ]);

    $response->assertRedirect();
    expect($order->fresh()->status)->toBe('packed');

    $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), [
        'status' => 'out_for_delivery',
    ]);
    expect($order->fresh()->status)->toBe('out_for_delivery');

    $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), [
        'status' => 'delivered',
    ]);
    expect($order->fresh()->status)->toBe('delivered');
});

test('vendor B cannot modify or claim order already claimed by vendor A', function () {
    $vendorA = User::factory()->vendor()->create();
    $vendorB = User::factory()->vendor()->create();

    $order = Order::factory()->create([
        'vendor_id' => $vendorA->id,
        'status' => 'packed',
    ]);

    $response = $this->actingAs($vendorB)->patch(route('vendor.orders.update-status', $order->id), [
        'status' => 'out_for_delivery',
    ]);

    $response->assertSessionHasErrors(['order']);
    expect($order->fresh()->vendor_id)->toBe($vendorA->id)
        ->and($order->fresh()->status)->toBe('packed');
});

test('whatsapp dispatch service generates formatted URI with customer phone items and Google Maps pin', function () {
    $order = Order::factory()->create([
        'order_number' => 'BZ-TEST1234',
        'customer_name' => 'Amina Bauchi',
        'customer_phone' => '09031704109',
        'delivery_address' => 'Room 12, Bauchi Hostel, Gwallameji',
        'latitude' => 10.2847000,
        'longitude' => 9.7915000,
        'subtotal' => 3000.00,
        'delivery_fee' => 500.00,
        'total' => 3500.00,
        'status' => 'pending',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Trophy Lager (600ml)',
        'quantity' => 2,
        'unit_price' => 850.00,
        'subtotal' => 1700.00,
    ]);

    $dispatchService = new WhatsAppDispatchService;
    $url = $dispatchService->generateDispatchLink($order, '08099887766');

    expect($url)->toContain('https://wa.me/2348099887766?text=')
        ->and($url)->toContain('BZ-TEST1234')
        ->and($url)->toContain(rawurlencode('Amina Bauchi'))
        ->and($url)->toContain(rawurlencode('maps.google.com/?q=10.2847,9.7915'))
        ->and($url)->toContain(rawurlencode('Trophy Lager'));
});

test('vendor can cancel order and stock level is automatically restored', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $product = Product::factory()->vendor($vendor)->create([
        'stock_level' => 10,
    ]);

    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'status' => 'pending',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 1000.00,
        'subtotal' => 3000.00,
    ]);

    $response = $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), [
        'status' => 'cancelled',
    ]);

    $response->assertRedirect();
    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock_level)->toBe(13);

    // Subsequent updates to cancelled status should not re-increment stock
    $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), [
        'status' => 'cancelled',
    ]);
    expect($product->fresh()->stock_level)->toBe(13);
});

test('vendor can export daily sales log as CSV report', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'status' => 'delivered',
        'subtotal' => 5000.00,
        'delivery_fee' => 500.00,
        'total' => 5500.00,
    ]);

    $response = $this->actingAs($vendor)->get(route('vendor.orders.export-csv', ['date' => 'today']));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('Order #')
        ->toContain($order->order_number);
});

test('vendor can view rider quick-dispatch view with active orders', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'status' => 'packed',
        'customer_phone' => '08012345678',
        'latitude' => 10.2847,
        'longitude' => 9.7915,
    ]);

    $response = $this->actingAs($vendor)->get(route('vendor.orders.rider'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendor/Orders/RiderDispatch')
            ->has('orders', 1)
        );
});
