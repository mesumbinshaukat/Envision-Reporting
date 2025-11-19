<?php

namespace App\Traits;

use App\Models\Currency;

trait HandlesCurrency
{
    /**
     * Resolve the user ID currencies should belong to.
     */
    protected function resolveCurrencyOwnerId(): ?int
    {
        if (auth()->guard('employee')->check()) {
            return optional(auth()->guard('employee')->user())->admin_id;
        }

        return auth()->id();
    }

    /**
     * Get base currency for current user
     */
    protected function getBaseCurrency()
    {
        $userId = $this->resolveCurrencyOwnerId();

        if (!$userId) {
            return null;
        }

        $query = Currency::where('user_id', $userId)
            ->where('is_active', true);

        $baseCurrency = (clone $query)->where('is_base', true)->first();

        return $baseCurrency ?? $query->orderBy('code')->first();
    }

    /**
     * Get all active currencies for current user
     */
    protected function getUserCurrencies()
    {
        $userId = $this->resolveCurrencyOwnerId();

        if (!$userId) {
            return collect();
        }

        return Currency::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('is_base', 'desc')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get currency or default to base
     */
    protected function getCurrencyOrBase($currencyId = null)
    {
        $userId = $this->resolveCurrencyOwnerId();

        if ($currencyId && $userId) {
            return Currency::where('user_id', $userId)
                ->where('is_active', true)
                ->where('id', $currencyId)
                ->first() ?? $this->getBaseCurrency();
        }

        return $this->getBaseCurrency();
    }

    /**
     * Format amount with currency
     */
    protected function formatCurrency($amount, $currencyId = null, $decimals = 2)
    {
        $currency = $this->getCurrencyOrBase($currencyId);
        
        if ($currency) {
            return $currency->format($amount, $decimals);
        }
        
        return 'Rs.' . number_format($amount, $decimals);
    }
}
