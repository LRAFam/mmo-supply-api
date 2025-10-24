<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;

class ServiceController extends BaseProductController
{
    /**
     * Get the ProductType enum for Service
     *
     * @return ProductType
     */
    protected function getProductType(): ProductType
    {
        return ProductType::SERVICE;
    }

    /**
     * Get validation rules specific to Service products
     *
     * @param bool $isUpdate Whether these rules are for an update operation
     * @return array
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return array_merge($this->getCommonValidationRules($isUpdate), [
            // Service-specific fields
            'pricing_mode' => 'nullable|string|in:fixed,package_based',
            'estimated_time' => 'nullable|string',
            'packages' => 'nullable|array',
            'packages.*.name' => 'required_with:packages|string',
            'packages.*.price' => 'required_with:packages|numeric|min:0',
            'packages.*.features' => 'nullable',
            'addons' => 'nullable|array',
            'addons.*.name' => 'required_with:addons|string',
            'addons.*.description' => 'nullable|string',
            'addons.*.price' => 'required_with:addons|numeric|min:0',
            'schedule' => 'nullable|array',
            'max_concurrent_orders' => 'nullable|integer|min:1',
            'service_type' => 'nullable|string',
            'boosting_config' => 'nullable|array',
        ]);
    }
}
