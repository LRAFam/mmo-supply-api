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
            'estimated_time' => 'nullable|string',
            'packages' => 'nullable|array',
            'addons' => 'nullable|array',
            'schedule' => 'nullable|array',
            'max_concurrent_orders' => 'nullable|integer|min:1',
            'service_type' => 'nullable|string',
            'boosting_config' => 'nullable|array',
        ]);
    }
}
