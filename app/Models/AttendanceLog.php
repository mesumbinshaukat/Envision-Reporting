<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_user_id',
        'attendance_id',
        'action',
        'failure_reason',
        'latitude',
        'longitude',
        'distance_from_office',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'additional_info',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance_from_office' => 'decimal:2',
    ];

    /**
     * Get the employee user that owns the log.
     */
    public function employeeUser()
    {
        return $this->belongsTo(EmployeeUser::class);
    }

    /**
     * Get the attendance record associated with this log.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Scope to filter by employee.
     */
    public function scopeForEmployee($query, $employeeUserId)
    {
        return $query->where('employee_user_id', $employeeUserId);
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter failed attempts.
     */
    public function scopeFailedAttempts($query)
    {
        return $query->whereIn('action', ['check_in_failed', 'check_out_failed']);
    }

    /**
     * Scope to filter successful attempts.
     */
    public function scopeSuccessfulAttempts($query)
    {
        return $query->whereIn('action', ['check_in_success', 'check_out_success']);
    }

    /**
     * Get formatted location string.
     */
    public function getFormattedLocationAttribute(): string
    {
        if ($this->latitude && $this->longitude) {
            return number_format($this->latitude, 6) . ', ' . number_format($this->longitude, 6);
        }
        return 'N/A';
    }

    /**
     * Get formatted distance.
     */
    public function getFormattedDistanceAttribute(): string
    {
        if ($this->distance_from_office !== null) {
            return number_format($this->distance_from_office, 2) . ' meters';
        }
        return 'N/A';
    }
}
