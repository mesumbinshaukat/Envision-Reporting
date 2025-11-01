<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'created_by_employee_id',
        'deleted_by_employee_id',
        'name',
        'email',
        'primary_contact',
        'secondary_contact',
        'picture',
        'website',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function createdByEmployee()
    {
        return $this->belongsTo(EmployeeUser::class, 'created_by_employee_id');
    }

    public function deletedByEmployee()
    {
        return $this->belongsTo(EmployeeUser::class, 'deleted_by_employee_id');
    }
}
