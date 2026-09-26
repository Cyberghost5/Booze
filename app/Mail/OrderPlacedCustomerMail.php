<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order #{$this->order->order_number} Confirmation - BoozeApp",
        );
    }

    public function content(): Content
    {
        $totalFormatted = '₦' . number_format($this->order->total, 2);
        $itemsList = $this->order->items->map(
            fn($i) => "• {$i->quantity}x {$i->product_name} (@ ₦" . number_format($i->unit_price, 2) . ")"
        )->implode("\n");

        return new Content(
            raw: "Hello {$this->order->customer_name},\n\nYour order #{$this->order->order_number} for {$totalFormatted} has been placed successfully!\n\nDelivery Address: {$this->order->delivery_address}\nItems Ordered:\n{$itemsList}\n\nThank you for ordering with BoozeApp Gwallameji!",
        );
    }
}
