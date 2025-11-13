<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryRelease extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_id',
        'exchange_rate_at_time',
        'employee_id',
        'month',
        'base_salary',
        'commission_amount',
        'bonus_amount',
        'deductions',
        'total_amount',
        'partial_amount',
        'release_date',
        'notes',
        'release_type',
    ];

    protected $casts = [
        'release_date' => 'date',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get total amount converted to base currency using historical exchange rate
     */
    public function getTotalAmountInBaseCurrency()
    {
        if (!$this->currency) {
            return $this->total_amount;
        }
        
        // Use historical exchange rate if available, otherwise use current rate
        if ($this->exchange_rate_at_time) {
            return $this->currency->is_base ? $this->total_amount : ($this->total_amount * $this->exchange_rate_at_time);
        }
        
        return $this->currency->toBase($this->total_amount);
    }
}
