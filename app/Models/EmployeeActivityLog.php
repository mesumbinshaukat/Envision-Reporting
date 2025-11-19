<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'employee_user_id',
        'category',
        'action',
        'summary',
        'description',
        'request_method',
        'route_name',
        'request_path',
        'referer',
        'response_status',
        'ip_address',
        'ip_address_v4',
        'ip_address_v6',
        'device_type',
        'browser',
        'os',
        'user_agent',
        'request_payload',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected $appends = [
        'employee_display_name',
    ];

    /**
     * Admin owner of the employee user.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Employee user associated with the activity.
     */
    public function employeeUser(): BelongsTo
    {
        return $this->belongsTo(EmployeeUser::class);
    }

    public function getEmployeeDisplayNameAttribute(): string
    {
        $employeeUser = $this->employeeUser;

        if ($employeeUser?->name) {
            return $employeeUser->name;
        }

        if ($employeeUser?->employee?->name) {
            return $employeeUser->employee->name;
        }

        return 'Unknown Employee';
    }
}
