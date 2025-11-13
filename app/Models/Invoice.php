<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'employee_id',
        'currency_id',
        'exchange_rate_at_time',
        'status',
        'approval_status',
        'created_by_employee_id',
        'approved_at',
        'approved_by',
        'due_date',
        'amount',
        'paid_amount',
        'remaining_amount',
        'payment_date',
        'payment_month',
        'tax',
        'special_note',
        'commission_paid',
        'is_one_time',
        'one_time_client_name',
        'attachments',
        'payment_method',
        'custom_payment_method',
        'payment_processing_fee',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function createdByEmployee()
    {
        return $this->belongsTo(EmployeeUser::class, 'created_by_employee_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function milestones()
    {
        return $this->hasMany(InvoiceMilestone::class)->orderBy('order');
    }

    public function attachments()
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    public function calculateCommission()
    {
        if ($this->employee_id && $this->employee) {
            $netAmount = $this->amount - $this->tax;
            return $netAmount * ($this->employee->commission_rate / 100);
        }
        return 0;
    }
    
    public function getClientNameAttribute()
    {
        if ($this->is_one_time) {
            return $this->one_time_client_name ?? 'One-Time Project';
        }
        return $this->client ? $this->client->name : 'N/A';
    }

    /**
     * Convert a monetary amount to base currency honouring historical exchange rate.
     */
    public function convertAmountToBase(?float $amount): float
    {
        $amount = $amount ?? 0;

        if (!$this->currency || $this->currency->is_base) {
            return $amount;
        }

        if ($this->exchange_rate_at_time) {
            return $amount * $this->exchange_rate_at_time;
        }

        return $this->currency->toBase($amount);
    }

    /**
     * Get invoice amount converted to base currency using historical exchange rate
     */
    public function getAmountInBaseCurrency(): float
    {
        return $this->convertAmountToBase($this->amount);
    }

    /**
     * Get paid amount converted to base currency using historical exchange rate
     */
    public function getPaidAmountInBaseCurrency(): float
    {
        return $this->convertAmountToBase($this->paid_amount);
    }

    /**
     * Get remaining amount converted to base currency using historical exchange rate
     */
    public function getRemainingAmountInBaseCurrency(): float
    {
        return $this->convertAmountToBase($this->remaining_amount);
    }
}
