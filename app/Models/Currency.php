<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'name',
        'symbol',
        'country',
        'conversion_rate',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'conversion_rate' => 'decimal:6',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the currency
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convert amount from this currency to base currency
     */
    public function toBase($amount)
    {
        if ($this->is_base) {
            return $amount;
        }
        return $amount / $this->conversion_rate;
    }

    /**
     * Convert amount from base currency to this currency
     */
    public function fromBase($amount)
    {
        if ($this->is_base) {
            return $amount;
        }
        return $amount * $this->conversion_rate;
    }

    /**
     * Convert amount from this currency to another currency
     */
    public function convertTo(Currency $targetCurrency, $amount)
    {
        // Convert to base first, then to target
        $baseAmount = $this->toBase($amount);
        return $targetCurrency->fromBase($baseAmount);
    }

    /**
     * Format amount with currency symbol
     */
    public function format($amount, $decimals = 2)
    {
        return $this->symbol . number_format($amount, $decimals);
    }
}
