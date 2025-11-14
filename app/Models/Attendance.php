<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_user_id',
        'check_in',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_ip',
        'check_in_ip_v6',
        'check_in_user_agent',
        'check_in_distance_meters',
        'check_out',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_ip',
        'check_out_ip_v6',
        'check_out_user_agent',
        'check_out_distance_meters',
        'attendance_date',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'attendance_date' => 'date',
        'check_in_latitude' => 'decimal:8',
        'check_in_longitude' => 'decimal:8',
        'check_in_distance_meters' => 'decimal:2',
        'check_out_latitude' => 'decimal:8',
        'check_out_longitude' => 'decimal:8',
        'check_out_distance_meters' => 'decimal:2',
    ];

    /**
     * Get the employee user that owns the attendance.
     */
    public function employeeUser()
    {
        return $this->belongsTo(EmployeeUser::class);
    }

    /**
     * Get the fix requests for this attendance.
     */
    public function fixRequests()
    {
        return $this->hasMany(AttendanceFixRequest::class);
    }

    /**
     * Get pending fix requests for this attendance.
     */
    public function pendingFixRequests()
    {
        return $this->hasMany(AttendanceFixRequest::class)->where('status', 'pending');
    }

    /**
     * Get the logs for this attendance.
     */
    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Check if the employee has checked in.
     */
    public function hasCheckedIn(): bool
    {
        return !is_null($this->check_in);
    }

    /**
     * Check if the employee has checked out.
     */
    public function hasCheckedOut(): bool
    {
        return !is_null($this->check_out);
    }

    /**
     * Calculate work duration in hours.
     */
    public function getWorkDurationAttribute(): ?float
    {
        if ($this->check_in && $this->check_out) {
            return $this->check_in->diffInHours($this->check_out, true);
        }

        return null;
    }

    /**
     * Get formatted work duration.
     */
    public function getFormattedWorkDurationAttribute(): ?string
    {
        if ($this->check_in && $this->check_out) {
            $diff = $this->check_in->diff($this->check_out);
            return sprintf('%dh %dm', $diff->h + ($diff->days * 24), $diff->i);
        }

        return null;
    }

    /**
     * Scope to filter by employee user.
     */
    public function scopeForEmployee($query, $employeeUserId)
    {
        return $query->where('employee_user_id', $employeeUserId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by current month.
     */
    public function scopeCurrentMonth($query)
    {
        $now = Carbon::now();
        return $query->whereYear('attendance_date', $now->year)
                     ->whereMonth('attendance_date', $now->month);
    }

    /**
     * Scope to filter checked in but not checked out.
     */
    public function scopeCheckedInOnly($query)
    {
        return $query->whereNotNull('check_in')->whereNull('check_out');
    }

    /**
     * Scope to filter completed attendance (both check-in and check-out).
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('check_in')->whereNotNull('check_out');
    }
}
