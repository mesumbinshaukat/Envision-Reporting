<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bonus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_id',
        'exchange_rate_at_time',
        'employee_id',
        'amount',
        'description',
        'date',
        'release_type',
        'released',
    ];

    protected $casts = [
        'date' => 'date',
        'released' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get bonus amount converted to base currency using historical exchange rate
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
