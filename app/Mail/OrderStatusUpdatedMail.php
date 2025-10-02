<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {
        // Log email details
        Log::info('🔄 ORDER STATUS UPDATED EMAIL', [
            'to' => $order->buyer->email,
            'buyer_name' => $order->buyer->name,
            'order_number' => $order->order_number,
            'order_id' => $order->id,
            'status_change' => "{$oldStatus} → {$newStatus}",
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'seller_notes' => $order->seller_notes,
            'total' => $order->total,
            'items_count' => $order->items->count(),
            'subject' => "Order #{$order->order_number} Status Updated",
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order #{$this->order->order_number} Status Updated",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status-updated',
            with: [
                'order' => $this->order,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'buyerName' => $this->order->buyer->name,
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
