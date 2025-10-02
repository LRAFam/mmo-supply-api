<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderDeliveredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public OrderItem $orderItem,
        public string $deliveryDetails
    ) {
        // Log email details
        Log::info('📦 ORDER DELIVERED EMAIL', [
            'to' => $order->buyer->email,
            'buyer_name' => $order->buyer->name,
            'order_number' => $order->order_number,
            'order_id' => $order->id,
            'product_name' => $orderItem->product_name,
            'game_name' => $orderItem->game_name,
            'quantity' => $orderItem->quantity,
            'total' => $orderItem->total,
            'delivery_details' => $deliveryDetails,
            'subject' => "Order #{$order->order_number} - {$orderItem->product_name} Delivered",
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order #{$this->order->order_number} - {$this->orderItem->product_name} Delivered",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-delivered',
            with: [
                'order' => $this->order,
                'orderItem' => $this->orderItem,
                'deliveryDetails' => $this->deliveryDetails,
                'buyerName' => $this->order->buyer->name,
                'sellerName' => $this->orderItem->seller->name,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
