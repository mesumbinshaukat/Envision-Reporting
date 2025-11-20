<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_user_id' => $this->employee_user_id,
            'attendance_date' => $this->attendance_date?->toDateString(),
            'check_in' => $this->check_in?->toISOString(),
            'check_in_latitude' => $this->check_in_latitude,
            'check_in_longitude' => $this->check_in_longitude,
            'check_in_ip' => $this->check_in_ip,
            'check_in_ip_v6' => $this->check_in_ip_v6,
            'check_in_user_agent' => $this->check_in_user_agent,
            'check_in_distance_meters' => $this->check_in_distance_meters,
            'check_out' => $this->check_out?->toISOString(),
            'check_out_latitude' => $this->check_out_latitude,
            'check_out_longitude' => $this->check_out_longitude,
            'check_out_ip' => $this->check_out_ip,
            'check_out_ip_v6' => $this->check_out_ip_v6,
            'check_out_user_agent' => $this->check_out_user_agent,
            'check_out_distance_meters' => $this->check_out_distance_meters,
            'work_duration' => $this->work_duration,
            'formatted_work_duration' => $this->formatted_work_duration,
            'has_checked_in' => $this->hasCheckedIn(),
            'has_checked_out' => $this->hasCheckedOut(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'employee_user' => new EmployeeUserResource($this->whenLoaded('employeeUser')),
            'fix_requests' => AttendanceFixRequestResource::collection($this->whenLoaded('fixRequests')),
            'logs' => AttendanceLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
