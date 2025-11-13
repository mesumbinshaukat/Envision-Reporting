<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_id',
        'name',
        'marital_status',
        'primary_contact',
        'email',
        'role',
        'secondary_contact',
        'employment_type',
        'joining_date',
        'last_date',
        'salary',
        'commission_rate',
        'geolocation_required',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'last_date' => 'date',
        'geolocation_required' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function salaryReleases()
    {
        return $this->hasMany(SalaryRelease::class);
    }

    public function employeeUser()
    {
        return $this->hasOne(EmployeeUser::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function hasUserAccount()
    {
        return $this->employeeUser()->exists();
    }
}
