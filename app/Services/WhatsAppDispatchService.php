<?php

namespace App\Services;

use App\Models\Order;

class WhatsAppDispatchService
{
    /**
     * Generate pre-filled WhatsApp URI for vendor internal delivery staff dispatch.
     */
    public function generateDispatchLink(Order $order, string $staffPhone = ''): string
    {
        $order->loadMissing('items');

        $itemsList = $order->items->map(function ($item) {
            $unitPriceFormatted = number_format($item->unit_price, 2);
            $subtotalFormatted = number_format($item->subtotal, 2);
            return "• {$item->quantity}x {$item->product_name} (@ ₦{$unitPriceFormatted} = ₦{$subtotalFormatted})";
        })->implode("\n");

        $mapsUrl = "https://maps.google.com/?q={$order->latitude},{$order->longitude}";

        $message = implode("\n", [
            "*DISPATCH ORDER - BOOZE APP GWALLAMEJI*",
            "----------------------------------------",
            "*Order #:* {$order->order_number}",
            "*Customer:* {$order->customer_name}",
            "*Phone:* {$order->customer_phone}",
            "*Address:* {$order->delivery_address}",
            "*Maps Location:* {$mapsUrl}",
            "",
            "*ORDERED ITEMS:*",
            $itemsList,
            "",
            "*Subtotal:* ₦" . number_format($order->subtotal, 2),
            "*Delivery Fee:* ₦" . number_format($order->delivery_fee, 2),
            "*TOTAL TO COLLECT:* ₦" . number_format($order->total, 2),
            "----------------------------------------",
            "*Status:* " . strtoupper($order->status),
        ]);

        $cleanPhone = PhoneNumberService::formatForWhatsApp($staffPhone);

        $encodedMessage = rawurlencode($message);

        return $cleanPhone
            ? "https://wa.me/{$cleanPhone}?text={$encodedMessage}"
            : "https://wa.me/?text={$encodedMessage}";
    }
}
