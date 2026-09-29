<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

test('consumer can log in via phone number and password', function () {
    $user = User::factory()->consumer()->create([
        'phone' => '09031704109',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->post(route('auth.phone-login'), [
        'phone' => '09031704109',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    expect(Auth::id())->toBe($user->id);
});

test('consumer can register via phone and verify 6-digit OTP', function () {
    Http::fake([
        'https://www.bulksmsnigeria.com/api/v2/sms/create' => Http::response(['status' => 'success'], 200),
    ]);

    $registerResponse = $this->post(route('auth.phone-register'), [
        'name' => 'Amina Bauchi',
        'phone' => '08099887766',
        'password' => 'secret123',
    ]);

    $registerResponse->assertOk()
        ->assertJson(['success' => true, 'requires_otp' => true]);

    $user = User::where('phone', '+2348099887766')->first();
    expect($user)->not->toBeNull()
        ->and($user->phone_otp)->not->toBeNull();

    $verifyResponse = $this->post(route('auth.phone-verify-otp'), [
        'phone' => '08099887766',
        'otp' => $user->phone_otp,
    ]);

    $verifyResponse->assertOk()
        ->assertJson(['success' => true]);

    expect(Auth::id())->toBe($user->id)
        ->and($user->fresh()->phone_verified_at)->not->toBeNull();
});

test('unauthenticated user cannot place an order without logging in', function () {
    $vendor = User::factory()->vendor()->create();
    $product = Product::factory()->vendor($vendor)->create(['stock_level' => 10]);

    $response = $this->post(route('consumer.checkout'), [
        'customer_name' => 'Guest User',
        'customer_phone' => '09031704109',
        'delivery_address' => 'Gwallameji Gate',
        'latitude' => 10.284700,
        'longitude' => 9.791500,
        'dob' => '2000-01-01',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertSessionHasErrors(['auth']);
});

test('consumer cannot place 2 active orders at the same time', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $product = Product::factory()->vendor($vendor)->create(['stock_level' => 10]);

    // Active order 1 in progress
    Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'status' => 'pending',
    ]);

    // Attempt placing a second order
    $response = $this->actingAs($consumer)->post(route('consumer.checkout'), [
        'customer_name' => $consumer->name,
        'customer_phone' => $consumer->phone,
        'delivery_address' => 'Hostel 1, Gwallameji',
        'latitude' => 10.284700,
        'longitude' => 9.791500,
        'dob' => '2000-01-01',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $response->assertSessionHasErrors(['active_order']);
});

test('consumer can cancel their own pending order and stock level is restored', function () {
    $vendor = User::factory()->vendor()->create();
    $consumer = User::factory()->consumer()->create();

    $product = Product::factory()->vendor($vendor)->create(['stock_level' => 10]);

    $order = Order::factory()->create([
        'user_id' => $consumer->id,
        'vendor_id' => $vendor->id,
        'status' => 'pending',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($consumer)->patch(route('consumer.orders.cancel', $order->id));

    $response->assertRedirect();
    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock_level)->toBe(12);
});

test('user can request password reset OTP via phone and reset password successfully', function () {
    Http::fake([
        'https://www.bulksmsnigeria.com/api/v2/sms/create' => Http::response(['status' => 'success'], 200),
    ]);

    $user = User::factory()->consumer()->create([
        'phone' => '+2349031704109',
        'password' => Hash::make('oldpassword123'),
    ]);

    // Step 1: Request Password Reset OTP
    $forgotResponse = $this->post(route('auth.phone-forgot-password'), [
        'phone' => '09031704109',
    ]);

    $forgotResponse->assertOk()
        ->assertJson(['success' => true, 'requires_reset_otp' => true]);

    $user->refresh();
    expect($user->phone_otp)->not->toBeNull();

    // Step 2: Reset Password using OTP
    $resetResponse = $this->post(route('auth.phone-reset-password'), [
        'phone' => '09031704109',
        'otp' => $user->phone_otp,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $resetResponse->assertOk()
        ->assertJson(['success' => true]);

    expect(Auth::id())->toBe($user->id);
    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

test('password reset fails with invalid OTP or non-existent phone', function () {
    $forgotResponse = $this->post(route('auth.phone-forgot-password'), [
        'phone' => '0000000000',
    ]);

    $forgotResponse->assertStatus(422)
        ->assertJson(['success' => false]);
});
