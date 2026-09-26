<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\WhatsAppDispatchService;
use Carbon\Carbon;

test('complete end-to-end booze app lifecycle: age gate -> checkout -> vendor feed -> whatsapp dispatch -> order status update', function () {
    // 1. Setup Vendor, Consumer, Category, and Products
    $vendor = User::factory()->vendor()->create([
        'name' => 'Gwallameji Central Liquor Store',
        'email' => 'gwallameji_vendor@booze.test',
        'phone' => '09031704109',
    ]);

    $consumer = User::factory()->consumer()->create([
        'name' => 'Emeka Student',
        'email' => 'emeka@booze.test',
        'phone' => '08069876543',
    ]);

    $beers = Category::factory()->create(['name' => 'Beers', 'slug' => 'beers']);

    $trophy = Product::factory()->vendor($vendor)->create([
        'category_id' => $beers->id,
        'name' => 'Trophy Lager (600ml)',
        'cost_price' => 650.00,
        'selling_price' => 850.00,
        'stock_level' => 24,
    ]);

    $guinness = Product::factory()->vendor($vendor)->create([
        'category_id' => $beers->id,
        'name' => 'Guinness Extra Stout (600ml)',
        'cost_price' => 850.00,
        'selling_price' => 1100.00,
        'stock_level' => 12,
    ]);

    // 2. Consumer Browses Public Catalog
    $catalogResponse = $this->get(route('consumer.catalog'));
    $catalogResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Consumer/Catalog')
            ->has('products', 2)
        );

    // 3. Consumer Places Order via Checkout with 18+ DOB & Pin Drop Coordinates
    $validDob = Carbon::now()->subYears(21)->format('Y-m-d');

    $checkoutResponse = $this->actingAs($consumer)->post(route('consumer.checkout'), [
        'customer_name' => 'Emeka Student',
        'customer_phone' => '08069876543',
        'delivery_address' => 'Executive Lodge 14, Gwallameji, Bauchi',
        'latitude' => 10.2847000,
        'longitude' => 9.7915000,
        'dob' => $validDob,
        'notes' => 'Please bring cold drinks',
        'items' => [
            ['product_id' => $trophy->id, 'quantity' => 2],   // 2 * 850 = 1700
            ['product_id' => $guinness->id, 'quantity' => 1], // 1 * 1100 = 1100
        ],
    ]);

    // Subtotal = 2800. Delivery fee = 500. Total = 3300.
    $order = Order::where('customer_name', 'Emeka Student')->first();
    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('pending')
        ->and((float) $order->subtotal)->toBe(2800.0)
        ->and((float) $order->delivery_fee)->toBe(500.0)
        ->and((float) $order->total)->toBe(3300.0);

    $checkoutResponse->assertRedirect(route('consumer.orders.show', $order->id));

    // Verify stock levels decremented in real-time
    expect($trophy->fresh()->stock_level)->toBe(22)
        ->and($guinness->fresh()->stock_level)->toBe(11);

    // 4. Vendor Views Orders & Bookkeeping Feed
    // Total Sales = 2800. Total COGS = (2*650) + (1*850) = 2150. Net Profit = 650.
    $vendorFeedResponse = $this->actingAs($vendor)->get(route('vendor.orders.index'));

    $vendorFeedResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendor/Orders/Index')
            ->has('orders', 1)
            ->where('ledger.totalSales', 2800)
            ->where('ledger.totalCogs', 2150)
            ->where('ledger.netProfit', 650)
        );

    // 5. Vendor One-Click WhatsApp Dispatch Link Generation
    $dispatchService = new WhatsAppDispatchService();
    $whatsappUrl = $dispatchService->generateDispatchLink($order, $vendor->phone);

    expect($whatsappUrl)->toContain('https://wa.me/2349031704109?text=')
        ->and($whatsappUrl)->toContain($order->order_number)
        ->and($whatsappUrl)->toContain(rawurlencode('Emeka Student'))
        ->and($whatsappUrl)->toContain(rawurlencode('Executive Lodge 14, Gwallameji, Bauchi'))
        ->and($whatsappUrl)->toContain(rawurlencode('maps.google.com/?q=10.2847,9.7915'));

    // 6. Vendor Updates Order Status: Pending -> Packed -> Out for Delivery -> Delivered
    $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), ['status' => 'packed']);
    expect($order->fresh()->status)->toBe('packed');

    $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), ['status' => 'out_for_delivery']);
    expect($order->fresh()->status)->toBe('out_for_delivery');

    $this->actingAs($vendor)->patch(route('vendor.orders.update-status', $order->id), ['status' => 'delivered']);
    expect($order->fresh()->status)->toBe('delivered');

    // 7. Consumer Order Status Tracker Reflects Delivered State
    $consumerTrackerResponse = $this->get(route('consumer.orders.show', $order->id));
    $consumerTrackerResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Consumer/OrderShow')
            ->where('order.id', $order->id)
            ->where('order.status', 'delivered')
        );
});
