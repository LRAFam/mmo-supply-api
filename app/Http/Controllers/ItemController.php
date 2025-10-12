<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;

class ItemController extends BaseProductController
{
    /**
     * Get the ProductType enum for Item
     *
     * @return ProductType
     */
    protected function getProductType(): ProductType
    {
        return ProductType::ITEM;
    }

    /**
     * Get validation rules specific to Item products
     *
     * @param bool $isUpdate Whether these rules are for an update operation
     * @return array
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return array_merge($this->getCommonValidationRules($isUpdate), [
            // Item-specific fields
            'stock' => "{$rule}|integer|min:1",
            'min_quantity' => 'nullable|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1',
            'variants' => 'nullable|array',
            'delivery_time' => 'nullable|string',
        ]);
    }
}
