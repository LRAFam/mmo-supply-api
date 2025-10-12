<?php

namespace App\Enums;

enum ProductType: string
{
    case CURRENCY = 'currency';
    case ITEM = 'item';
    case SERVICE = 'service';
    case ACCOUNT = 'account';

    /**
     * Get the plural form of the product type
     */
    public function pluralize(): string
    {
        return match($this) {
            self::CURRENCY => 'currencies',
            default => $this->value . 's',
        };
    }

    /**
     * Get the singular form (just the enum value)
     */
    public function singularize(): string
    {
        return $this->value;
    }

    /**
     * Create ProductType from string, normalizing both singular and plural forms
     */
    public static function fromString(string $type): self
    {
        $normalized = strtolower(trim($type));

        return match($normalized) {
            'currency', 'currencies' => self::CURRENCY,
            'item', 'items' => self::ITEM,
            'service', 'services' => self::SERVICE,
            'account', 'accounts' => self::ACCOUNT,
            default => throw new \InvalidArgumentException("Invalid product type: {$type}"),
        };
    }

    /**
     * Try to create ProductType from string, return null if invalid
     */
    public static function tryFromString(string $type): ?self
    {
        try {
            return self::fromString($type);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Get the model class for this product type
     */
    public function getModelClass(): string
    {
        return match($this) {
            self::CURRENCY => \App\Models\Currency::class,
            self::ITEM => \App\Models\Item::class,
            self::SERVICE => \App\Models\Service::class,
            self::ACCOUNT => \App\Models\Account::class,
        };
    }

    /**
     * Get the price field name for this product type
     */
    public function getPriceField(): string
    {
        return match($this) {
            self::CURRENCY => 'price_per_unit',
            default => 'price',
        };
    }

    /**
     * Check if this product type has stock management
     */
    public function hasStock(): bool
    {
        return match($this) {
            self::CURRENCY, self::ITEM => true,
            self::SERVICE, self::ACCOUNT => false,
        };
    }

    /**
     * Get all product types as array of strings (singular)
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all product types as array of strings (plural)
     */
    public static function pluralValues(): array
    {
        return array_map(fn($case) => $case->pluralize(), self::cases());
    }
}
