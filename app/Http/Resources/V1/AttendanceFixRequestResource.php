<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceFixRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_id' => $this->attendance_id,
            'employee_user_id' => $this->employee_user_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'requested_check_in' => $this->requested_check_in?->toISOString(),
            'requested_check_out' => $this->requested_check_out?->toISOString(),
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at?->toISOString(),
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'attendance' => new AttendanceResource($this->whenLoaded('attendance')),
            'employee_user' => new EmployeeUserResource($this->whenLoaded('employeeUser')),
            'processed_by_user' => new UserResource($this->whenLoaded('processedBy')),
        ];
    }
}
