<?php

namespace App\Services;

use App\Mail\OrderPlacedCustomerMail;
use App\Mail\OrderPlacedVendorMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrderNotificationService
{
    public function __construct(
        protected BulkSmsNigeriaService $smsService
    ) {}

    /**
     * Immediately send order notification alerts to both Customer and Vendor via Email & BulkSMS Nigeria.
     */
    public function sendOrderPlacedNotifications(Order $order): void
    {
        $order->loadMissing(['items.product', 'vendor', 'user']);

        $totalFormatted = '₦' . number_format($order->total, 2);

        // 1. NOTIFY CUSTOMER
        $customerPhone = $order->customer_phone;
        $customerEmail = $order->user?->email;

        // Customer SMS via BulkSMS Nigeria
        $customerSmsText = "BoozeApp: Your order #{$order->order_number} (Total: {$totalFormatted}) has been received! Address: {$order->delivery_address}. Track status live in app.";
        $this->smsService->sendSms($customerPhone, $customerSmsText);

        // Customer Email
        if ($customerEmail) {
            try {
                Mail::to($customerEmail)->send(new OrderPlacedCustomerMail($order));
            } catch (\Throwable $e) {
                Log::error("Failed to send customer email for order #{$order->order_number}: " . $e->getMessage());
            }
        }

        // 2. NOTIFY VENDOR
        $vendor = $order->vendor ?? User::where('role', 'vendor_admin')->first();

        if ($vendor) {
            $vendorPhone = $vendor->phone;
            $vendorEmail = $vendor->email;

            // Vendor SMS via BulkSMS Nigeria
            $vendorSmsText = "BoozeApp Alert: New order #{$order->order_number} received from {$order->customer_name}. Customer Phone: {$order->customer_phone}. Address: {$order->delivery_address}. Total: {$totalFormatted}. Log in to dispatch!";
            if ($vendorPhone) {
                $this->smsService->sendSms($vendorPhone, $vendorSmsText);
            }

            // Vendor Email
            if ($vendorEmail) {
                try {
                    Mail::to($vendorEmail)->send(new OrderPlacedVendorMail($order));
                } catch (\Throwable $e) {
                    Log::error("Failed to send vendor email for order #{$order->order_number}: " . $e->getMessage());
                }
            }
        }
    }
}
