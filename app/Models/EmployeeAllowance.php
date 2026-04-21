<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAllowance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'allowance_type_id',
        'currency_id',
        'amount',
        'exchange_rate_at_time',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate_at_time' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowanceType()
    {
        return $this->belongsTo(AllowanceType::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Scope for active allowances
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get allowance amount converted to base currency using historical exchange rate
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

    /**
     * Get formatted label for display (e.g., "Petrol Allowance")
     */
    public function getDisplayLabel(): string
    {
        return $this->allowanceType?->label ?? 'Allowance';
    }
}
