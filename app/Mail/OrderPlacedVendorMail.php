<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedVendorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 New Order Alert #{$this->order->order_number} - BoozeApp Vendor",
        );
    }

    public function content(): Content
    {
        $totalFormatted = '₦' . number_format($this->order->total, 2);
        $itemsList = $this->order->items->map(
            fn($i) => "• {$i->quantity}x {$i->product_name}"
        )->implode("\n");

        return new Content(
            raw: "New Order Received!\n\nOrder #: {$this->order->order_number}\nCustomer: {$this->order->customer_name} ({$this->order->customer_phone})\nAddress: {$this->order->delivery_address}\nTotal: {$totalFormatted}\n\nItems to Pack:\n{$itemsList}\n\nPlease log in to your vendor dashboard to dispatch the order.",
        );
    }
}
