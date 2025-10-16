<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordWebhookService
{
    protected $webhookUrl;
    protected $secret;

    public function __construct()
    {
        $this->webhookUrl = config('services.discord.webhook_url');
        $this->secret = config('services.discord.webhook_secret');
    }

    /**
     * Send webhook when a new listing is created
     */
    public function sendNewListing($listing, $type)
    {
        if (!$this->webhookUrl) {
            Log::warning('Discord webhook URL not configured');
            return false;
        }

        try {
            $payload = [
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? null,
                    'description' => $listing->description,
                    'price' => $listing->price,
                    'stock' => $listing->stock ?? null,
                    'image_url' => $listing->image_url ?? null,
                    'is_featured' => $listing->is_featured ?? false,
                ],
                'game' => [
                    'id' => $listing->game->id,
                    'title' => $listing->game->title,
                    'slug' => $listing->game->slug,
                ],
                'user' => [
                    'id' => $listing->user->id,
                    'name' => $listing->user->name,
                ],
                'type' => $type, // 'item', 'currency', 'account', 'service'
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
                'Content-Type' => 'application/json',
            ])->post($this->webhookUrl . '/listing-created', $payload);

            if ($response->successful()) {
                Log::info('Discord webhook sent successfully for listing: ' . $listing->id);
                return true;
            } else {
                Log::error('Discord webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Discord webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send webhook when a listing is featured
     */
    public function sendFeaturedListing($listing, $type)
    {
        if (!$this->webhookUrl) {
            return false;
        }

        try {
            $payload = [
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? null,
                    'description' => $listing->description,
                    'price' => $listing->price,
                    'image_url' => $listing->image_url ?? null,
                ],
                'game' => [
                    'id' => $listing->game->id,
                    'title' => $listing->game->title,
                    'slug' => $listing->game->slug,
                ],
                'user' => [
                    'id' => $listing->user->id,
                    'name' => $listing->user->name,
                ],
                'type' => $type,
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/listing-featured', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord featured webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send webhook when a sale is completed
     */
    public function sendSaleCompleted($sale, $listing, $type)
    {
        if (!$this->webhookUrl) {
            return false;
        }

        try {
            $payload = [
                'sale' => [
                    'id' => $sale->id,
                    'price' => $sale->price,
                ],
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? null,
                ],
                'game' => [
                    'id' => $listing->game->id,
                    'title' => $listing->game->title,
                    'slug' => $listing->game->slug,
                ],
                'seller' => [
                    'id' => $listing->user->id,
                    'name' => $listing->user->name,
                ],
                'buyer' => [
                    'id' => $sale->buyer_id,
                    'name' => $sale->buyer->name ?? 'Unknown',
                ],
                'type' => $type,
                'price' => $sale->price,
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/sale-completed', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord sale webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // User-Specific Notification Methods
    // ============================================================

    /**
     * Send user-specific sale notification
     */
    public function sendUserSale($user, $sale, $listing, $buyer)
    {
        if (!$this->webhookUrl || !$user->hasDiscordNotificationsEnabled()) {
            return false;
        }

        try {
            $payload = [
                'username' => $user->username ?? $user->name,
                'sale' => [
                    'id' => $sale->id,
                    'price' => $sale->total ?? $sale->price,
                    'quantity' => $sale->quantity ?? 1,
                    'transaction_id' => $sale->transaction_id ?? null,
                ],
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? $listing->title,
                    'image_url' => $listing->image_url ?? null,
                ],
                'buyer' => [
                    'name' => $buyer->name ?? $buyer->username ?? 'Unknown',
                ],
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/user/sale', $payload);

            if ($response->successful()) {
                Log::info('Discord user sale notification sent to: ' . $user->name);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Discord user sale webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send user-specific message notification
     */
    public function sendUserMessage($user, $message, $sender)
    {
        if (!$this->webhookUrl || !$user->hasDiscordNotificationsEnabled()) {
            return false;
        }

        try {
            $payload = [
                'username' => $user->username ?? $user->name,
                'message' => [
                    'id' => $message->id,
                    'content' => $message->content ?? $message->message,
                    'listing_id' => $message->listing_id ?? null,
                    'listing_title' => $message->listing_title ?? null,
                ],
                'sender' => [
                    'name' => $sender->name ?? $sender->username ?? 'Unknown',
                ],
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/user/message', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord user message webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send user-specific order notification
     */
    public function sendUserOrder($user, $order, $listing, $buyer)
    {
        if (!$this->webhookUrl || !$user->hasDiscordNotificationsEnabled()) {
            return false;
        }

        try {
            $payload = [
                'username' => $user->username ?? $user->name,
                'order' => [
                    'id' => $order->id,
                    'total' => $order->total,
                    'quantity' => $order->quantity ?? 1,
                ],
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? $listing->title,
                    'image_url' => $listing->image_url ?? null,
                ],
                'buyer' => [
                    'name' => $buyer->name ?? $buyer->username ?? 'Unknown',
                ],
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/user/order', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord user order webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send user-specific offer notification
     */
    public function sendUserOffer($user, $offer, $listing, $buyer)
    {
        if (!$this->webhookUrl || !$user->hasDiscordNotificationsEnabled()) {
            return false;
        }

        try {
            $payload = [
                'username' => $user->username ?? $user->name,
                'offer' => [
                    'id' => $offer->id,
                    'amount' => $offer->amount,
                    'message' => $offer->message ?? null,
                ],
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? $listing->title,
                    'price' => $listing->price,
                ],
                'buyer' => [
                    'name' => $buyer->name ?? $buyer->username ?? 'Unknown',
                ],
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/user/offer', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord user offer webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send user-specific review notification
     */
    public function sendUserReview($user, $review, $listing, $reviewer)
    {
        if (!$this->webhookUrl || !$user->hasDiscordNotificationsEnabled()) {
            return false;
        }

        try {
            $payload = [
                'username' => $user->username ?? $user->name,
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment ?? $review->review ?? null,
                ],
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                ],
                'reviewer' => [
                    'name' => $reviewer->name ?? $reviewer->username ?? 'Unknown',
                ],
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/user/review', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord user review webhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send low stock alert
     */
    public function sendLowStockAlert($user, $listing, $currentStock, $threshold = 5)
    {
        if (!$this->webhookUrl || !$user->hasDiscordNotificationsEnabled()) {
            return false;
        }

        try {
            $payload = [
                'username' => $user->username ?? $user->name,
                'listing' => [
                    'id' => $listing->id,
                    'title' => $listing->title ?? $listing->name,
                    'name' => $listing->name ?? $listing->title,
                    'image_url' => $listing->image_url ?? null,
                ],
                'current_stock' => $currentStock,
                'threshold' => $threshold,
            ];

            $response = Http::withHeaders([
                'X-Webhook-Secret' => $this->secret,
            ])->post($this->webhookUrl . '/user/low-stock', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord low stock webhook exception: ' . $e->getMessage());
            return false;
        }
    }
}
