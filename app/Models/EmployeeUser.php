<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class EmployeeUser extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'employee_id',
        'admin_id',
        'email',
        'password',
        'name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'created_by_employee_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'created_by_employee_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceFixRequests()
    {
        return $this->hasMany(AttendanceFixRequest::class);
    }

    // Helper method to check if this is an employee user
    public function isEmployee()
    {
        return true;
    }

    // Get the guard name
    public function getGuardName()
    {
        return 'employee';
    }
}
