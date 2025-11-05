<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_month',
        'notes',
        'commission_paid',
        'salary_release_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salaryRelease()
    {
        return $this->belongsTo(SalaryRelease::class);
    }

    /**
     * Get payment amount converted to base currency
     * Payment inherits currency from its invoice
     */
    public function getAmountInBaseCurrency()
    {
        if (!$this->invoice || !$this->invoice->currency) {
            return $this->amount;
        }
        return $this->invoice->currency->toBase($this->amount);
    }
}
