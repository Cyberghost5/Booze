<?php

use App\Mail\OrderPlacedCustomerMail;
use App\Mail\OrderPlacedVendorMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\BulkSmsNigeriaService;
use App\Services\OrderNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

test('order placement triggers email and BulkSMS Nigeria SMS notifications to both customer and vendor', function () {
    Mail::fake();
    config(['services.bulksms_nigeria.api_token' => 'test_bulksms_token']);

    Http::fake([
        'https://www.bulksmsnigeria.com/api/v2/sms/create' => Http::response(['status' => 'success'], 200),
    ]);

    $vendor = User::factory()->vendor()->create([
        'name' => 'Gwallameji Vendor Admin',
        'email' => 'vendor_admin@booze.test',
        'phone' => '08031234567',
    ]);

    $consumer = User::factory()->consumer()->create([
        'name' => 'Amina Bauchi',
        'email' => 'amina_student@booze.test',
        'phone' => '08051112233',
    ]);

    $product = Product::factory()->vendor($vendor)->create([
        'selling_price' => 1000.00,
        'stock_level' => 10,
    ]);

    $this->actingAs($consumer)->post(route('consumer.checkout'), [
        'customer_name' => 'Amina Bauchi',
        'customer_phone' => '08051112233',
        'delivery_address' => 'Federal Poly Hostel 1, Room 12',
        'latitude' => 10.285200,
        'longitude' => 9.792800,
        'dob' => '2000-05-15',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertRedirect();

    // Verify Customer Mail Sent
    Mail::assertSent(OrderPlacedCustomerMail::class, function ($mail) use ($consumer) {
        return $mail->hasTo($consumer->email);
    });

    // Verify Vendor Mail Sent
    Mail::assertSent(OrderPlacedVendorMail::class, function ($mail) use ($vendor) {
        return $mail->hasTo($vendor->email);
    });

    // Verify BulkSMS Nigeria API dispatched SMS for both customer and vendor
    Http::assertSentCount(2);
});

test('BulkSmsNigeriaService dispatches HTTP request when API token is configured', function () {
    config(['services.bulksms_nigeria.api_token' => 'test_secret_token_123']);
    config(['services.bulksms_nigeria.sender_id' => 'BoozeApp']);

    Http::fake([
        'https://www.bulksmsnigeria.com/api/v2/sms/create' => Http::response(['status' => 'success'], 200),
    ]);

    $smsService = new BulkSmsNigeriaService();
    $result = $smsService->sendSms('08031234567', 'Test SMS Message');

    expect($result)->toBeTrue();

    Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
        return $request->url() === 'https://www.bulksmsnigeria.com/api/v2/sms/create' &&
               $request['api_token'] === 'test_secret_token_123' &&
               $request['to'] === '2348031234567' &&
               $request['from'] === 'BoozeApp' &&
               $request['body'] === 'Test SMS Message';
    });
});
