<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
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
    ];

    protected $casts = [
        'joining_date' => 'date',
        'last_date' => 'date',
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
}
