<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;

class CurrencyController extends BaseProductController
{
    /**
     * Get the ProductType enum for Currency
     *
     * @return ProductType
     */
    protected function getProductType(): ProductType
    {
        return ProductType::CURRENCY;
    }

    /**
     * Get validation rules specific to Currency products
     *
     * @param bool $isUpdate Whether these rules are for an update operation
     * @return array
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return array_merge($this->getCommonValidationRules($isUpdate), [
            // Currency-specific fields
            'stock' => "{$rule}|integer|min:1",
            'min_amount' => 'nullable|integer|min:1',
            'max_amount' => 'nullable|integer|min:1',
            'amount' => 'nullable|string',
            'bulk_pricing' => 'nullable|array',

            // OSRS-specific fields for bulk stock currency
            'currency_stock_amount' => 'nullable|string', // e.g., "400M", "1B", "10B"
            'price_per_million' => 'nullable|numeric|min:0', // Rate per 1M gold
            'listing_intent' => 'nullable|string|in:selling,buying', // Whether selling or buying gold
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
        ]);
    }
}
