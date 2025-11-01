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
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
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
}
