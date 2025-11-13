<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_id',
        'exchange_rate_at_time',
        'description',
        'amount',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get expense amount converted to base currency using historical exchange rate
     */
    public function getAmountInBaseCurrency()
    {
        if (!$this->currency) {
            return $this->amount;
        }
        
        // Use historical exchange rate if available, otherwise use current rate
        if ($this->exchange_rate_at_time) {
            return $this->currency->is_base ? $this->amount : ($this->amount * $this->exchange_rate_at_time);
        }
        
        return $this->currency->toBase($this->amount);
    }
}
