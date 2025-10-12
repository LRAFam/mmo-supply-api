<?php

namespace App\Observers;

use App\Enums\ProductType;
use App\Services\DiscordWebhookService;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic Product Observer
 * Handles common product events (created, updated) for all product types
 * (Currency, Item, Service, Account)
 */
class ProductObserver
{
    protected $discordWebhook;

    public function __construct(DiscordWebhookService $discordWebhook)
    {
        $this->discordWebhook = $discordWebhook;
    }

    /**
     * Handle the product "created" event
     */
    public function created(Model $product): void
    {
        if ($product->is_active ?? true) {
            $productType = $this->getProductType($product);
            $this->discordWebhook->sendNewListing($product, $productType);
        }
    }

    /**
     * Handle the product "updated" event
     */
    public function updated(Model $product): void
    {
        if ($product->wasChanged('is_featured') && $product->is_featured) {
            $productType = $this->getProductType($product);
            $this->discordWebhook->sendFeaturedListing($product, $productType);
        }
    }

    /**
     * Get the product type string from the model class
     */
    private function getProductType(Model $product): string
    {
        return match(get_class($product)) {
            \App\Models\Currency::class => ProductType::CURRENCY->value,
            \App\Models\Item::class => ProductType::ITEM->value,
            \App\Models\Service::class => ProductType::SERVICE->value,
            \App\Models\Account::class => ProductType::ACCOUNT->value,
            default => 'product',
        };
    }
}
