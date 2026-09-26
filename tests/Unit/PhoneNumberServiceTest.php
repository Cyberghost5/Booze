<?php

use App\Services\PhoneNumberService;
use App\Models\User;
use App\Models\Order;

test('normalizes various Nigerian phone number formats to E.164 (+234...)', function () {
    expect(PhoneNumberService::normalize('09031704109'))->toBe('+2349031704109');
    expect(PhoneNumberService::normalize('9031704109'))->toBe('+2349031704109');
    expect(PhoneNumberService::normalize('2349031704109'))->toBe('+2349031704109');
    expect(PhoneNumberService::normalize('+234 903 170 4109'))->toBe('+2349031704109');
    expect(PhoneNumberService::normalize('0903 170 4109'))->toBe('+2349031704109');
    expect(PhoneNumberService::normalize('0812-345-6789'))->toBe('+2348123456789');
});

test('formats phone numbers for WhatsApp wa.me links', function () {
    expect(PhoneNumberService::formatForWhatsApp('09031704109'))->toBe('2349031704109');
    expect(PhoneNumberService::formatForWhatsApp('+234 903 170 4109'))->toBe('2349031704109');
});

test('User and Order models automatically normalize phone attribute on save', function () {
    $user = new User(['phone' => '09031704109']);
    expect($user->phone)->toBe('+2349031704109');

    $order = new Order(['customer_phone' => '0903 170 4109']);
    expect($order->customer_phone)->toBe('+2349031704109');
});
