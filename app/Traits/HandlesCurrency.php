<?php

namespace App\Traits;

use App\Models\Currency;
use App\Services\CurrencyService;

trait HandlesCurrency
{
    /**
     * Get base currency for current user
     */
    protected function getBaseCurrency()
    {
        $userId = auth()->id();
        return Currency::where('user_id', $userId)
            ->where('is_base', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active currencies for current user
     */
    protected function getUserCurrencies()
    {
        $userId = auth()->id();
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
        if ($currencyId) {
            $currency = Currency::find($currencyId);
            if ($currency && $currency->user_id === auth()->id()) {
                return $currency;
            }
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
