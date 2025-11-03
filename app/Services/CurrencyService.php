<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CurrencyService
{
    /**
     * Get base currency for a user
     */
    public function getBaseCurrency($userId)
    {
        return Currency::where('user_id', $userId)
            ->where('is_base', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active currencies for a user
     */
    public function getUserCurrencies($userId)
    {
        return Currency::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('is_base', 'desc')
            ->orderBy('code')
            ->get();
    }

    /**
     * Set base currency for a user
     */
    public function setBaseCurrency($userId, $currencyId)
    {
        DB::transaction(function () use ($userId, $currencyId) {
            // Remove base flag from all currencies
            Currency::where('user_id', $userId)->update(['is_base' => false]);
            
            // Set new base currency
            $currency = Currency::where('user_id', $userId)->findOrFail($currencyId);
            $currency->is_base = true;
            $currency->conversion_rate = 1;
            $currency->save();
            
            // Recalculate all conversion rates relative to new base
            $this->recalculateConversionRates($userId);
        });
    }

    /**
     * Recalculate conversion rates when base currency changes
     */
    protected function recalculateConversionRates($userId)
    {
        // This is a placeholder - in reality, you'd need to store
        // cross-rates or fetch them from an API
        // For now, we'll keep the rates as they are
    }

    /**
     * Convert amount between currencies
     */
    public function convert($amount, $fromCurrencyId, $toCurrencyId)
    {
        if ($fromCurrencyId == $toCurrencyId) {
            return $amount;
        }

        $fromCurrency = Currency::find($fromCurrencyId);
        $toCurrency = Currency::find($toCurrencyId);

        if (!$fromCurrency || !$toCurrency) {
            return $amount;
        }

        return $fromCurrency->convertTo($toCurrency, $amount);
    }

    /**
     * Convert amount to base currency
     */
    public function convertToBase($amount, $currencyId, $userId)
    {
        $currency = Currency::find($currencyId);
        $baseCurrency = $this->getBaseCurrency($userId);

        if (!$currency || !$baseCurrency) {
            return $amount;
        }

        return $currency->convertTo($baseCurrency, $amount);
    }

    /**
     * Format amount with currency
     */
    public function format($amount, $currencyId, $decimals = 2)
    {
        $currency = Currency::find($currencyId);
        
        if (!$currency) {
            return 'Rs.' . number_format($amount, $decimals);
        }

        return $currency->format($amount, $decimals);
    }

    /**
     * Create default PKR currency for new user
     */
    public function createDefaultCurrency($userId)
    {
        return Currency::create([
            'user_id' => $userId,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs.',
            'country' => 'Pakistan',
            'conversion_rate' => 1,
            'is_base' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Get available currency templates
     */
    public function getCurrencyTemplates()
    {
        return [
            'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => 'Rs.', 'country' => 'Pakistan'],
            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'country' => 'United States'],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'country' => 'United Kingdom'],
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'country' => 'European Union'],
            'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ', 'country' => 'United Arab Emirates'],
            'SAR' => ['name' => 'Saudi Riyal', 'symbol' => 'ر.س', 'country' => 'Saudi Arabia'],
            'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'country' => 'India'],
            'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'country' => 'China'],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'country' => 'Japan'],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'country' => 'Australia'],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'country' => 'Canada'],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'country' => 'Switzerland'],
        ];
    }
}
