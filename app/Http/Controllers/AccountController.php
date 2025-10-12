<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;

class AccountController extends BaseProductController
{
    /**
     * Get the ProductType enum for Account
     *
     * @return ProductType
     */
    protected function getProductType(): ProductType
    {
        return ProductType::ACCOUNT;
    }

    /**
     * Get validation rules specific to Account products
     *
     * @param bool $isUpdate Whether these rules are for an update operation
     * @return array
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        $rule = $isUpdate ? 'sometimes' : 'required';

        return array_merge($this->getCommonValidationRules($isUpdate), [
            // Account-specific fields
            'stock' => 'nullable|integer|min:1', // Accounts default to stock=1
            'account_level' => 'nullable|string',
            'rank' => 'nullable|string',
            'server_region' => 'nullable|string',
            'email_included' => 'nullable|boolean',
            'email_changeable' => 'nullable|boolean',
            'account_age_days' => 'nullable|integer|min:0',
            'included_items' => 'nullable|array',
            'included_items.*' => 'string',
            'account_stats' => 'nullable|array',
        ]);
    }

    /**
     * Apply custom filters specific to Account products
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function applyCustomFilters($query, \Illuminate\Http\Request $request): void
    {
        // Filter by account level
        if ($request->has('min_level')) {
            $query->where('account_level', '>=', $request->min_level);
        }
    }
}
