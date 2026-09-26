<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\BulkSmsNigeriaService;
use App\Services\OrderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('vendor sms alert explicitly contains customer phone number and delivery address', function () {
    $smsService = Mockery::mock(BulkSmsNigeriaService::class);
    
    $customer = User::factory()->create([
        'name' => 'John Doe',
        'phone' => '08012345678',
        'role' => 'consumer',
    ]);

    $vendor = User::factory()->create([
        'name' => 'Gwallameji Booze Store',
        'phone' => '09087654321',
        'role' => 'vendor_admin',
    ]);

    $order = Order::create([
        'user_id' => $customer->id,
        'vendor_id' => $vendor->id,
        'order_number' => 'BZ-TEST123',
        'status' => 'pending',
        'subtotal' => 3000.00,
        'delivery_fee' => 500.00,
        'total' => 3500.00,
        'customer_name' => 'John Doe',
        'customer_phone' => '08012345678',
        'delivery_address' => 'Room 12, Student Lodge A, Gwallameji',
        'latitude' => 10.2805,
        'longitude' => 9.8223,
    ]);

    // Expect customer SMS & vendor SMS with phone number
    $smsService->shouldReceive('sendSms')
        ->with('+2348012345678', Mockery::type('string'))
        ->once();

    $smsService->shouldReceive('sendSms')
        ->with('+2349087654321', Mockery::on(function ($message) {
            return str_contains($message, 'Customer Phone: +2348012345678')
                && str_contains($message, 'Room 12, Student Lodge A, Gwallameji');
        }))
        ->once();

    $notificationService = new OrderNotificationService($smsService);
    $notificationService->sendOrderPlacedNotifications($order);
});

test('consumer can view their previous orders on dashboard', function () {
    $user = User::factory()->create(['role' => 'consumer']);

    $order = Order::create([
        'user_id' => $user->id,
        'order_number' => 'BZ-HIST001',
        'status' => 'delivered',
        'subtotal' => 2000.00,
        'delivery_fee' => 500.00,
        'total' => 2500.00,
        'customer_name' => $user->name,
        'customer_phone' => '08011112222',
        'delivery_address' => 'Hostel 4, Gwallameji',
        'latitude' => 10.2805,
        'longitude' => 9.8223,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Consumer/Dashboard')
        ->has('orders', 1)
        ->where('orders.0.order_number', 'BZ-HIST001')
    );
});

test('consumer can update profile and default delivery address', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'role' => 'consumer',
        'phone' => '08099887766',
    ]);

    $response = $this->actingAs($user)->patch('/consumer/profile', [
        'name' => 'Updated Name',
        'email' => $user->email,
        'phone' => '08099887766',
        'default_address' => 'Block B, Executive Lodge, Gwallameji',
        'default_latitude' => 10.2850,
        'default_longitude' => 9.7910,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'default_address' => 'Block B, Executive Lodge, Gwallameji',
        'default_latitude' => 10.2850,
        'default_longitude' => 9.7910,
    ]);
});

test('checkout auto saves user default address when option selected', function () {
    $user = User::factory()->create([
        'role' => 'consumer',
        'phone' => '08033334444',
    ]);

    $product = Product::factory()->create([
        'stock_level' => 10,
        'selling_price' => 1500.00,
    ]);

    $response = $this->actingAs($user)->post('/checkout', [
        'customer_name' => 'Jane Student',
        'customer_phone' => '08033334444',
        'delivery_address' => 'New Default Villa, Gwallameji',
        'latitude' => 10.2840,
        'longitude' => 9.7920,
        'dob' => '2000-05-15',
        'save_as_default_address' => true,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'default_address' => 'New Default Villa, Gwallameji',
        'default_latitude' => 10.2840,
        'default_longitude' => 9.7920,
    ]);
});
