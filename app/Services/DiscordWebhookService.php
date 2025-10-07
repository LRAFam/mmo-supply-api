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
}
