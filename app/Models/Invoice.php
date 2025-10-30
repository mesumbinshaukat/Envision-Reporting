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
        'status',
        'due_date',
        'amount',
        'paid_amount',
        'remaining_amount',
        'payment_date',
        'payment_month',
        'tax',
        'special_note',
        'commission_paid',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
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

    public function calculateCommission()
    {
        if ($this->employee_id && $this->employee) {
            $netAmount = $this->amount - $this->tax;
            return $netAmount * ($this->employee->commission_rate / 100);
        }
        return 0;
    }
}
