<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'user_id' => $this->user_id,
            'currency_id' => $this->currency_id,
            'name' => $this->name,
            'marital_status' => $this->marital_status,
            'primary_contact' => $this->primary_contact,
            'email' => $this->email,
            'role' => $this->role,
            'secondary_contact' => $this->secondary_contact,
            'employment_type' => $this->employment_type,
            'joining_date' => $this->joining_date?->toDateString(),
            'last_date' => $this->last_date?->toDateString(),
            'salary' => $this->salary,
            'commission_rate' => $this->commission_rate,
            'geolocation_required' => $this->geolocation_required,
            'geolocation_mode' => $this->geolocation_mode,
            'geolocation_mode_label' => $this->geolocationModeLabel(),
            'profile_photo_url' => $this->profile_photo_url,
            'has_user_account' => $this->hasUserAccount(),
            'has_ip_whitelist' => $this->hasIpWhitelist(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relationships (loaded conditionally)
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'employee_user' => new EmployeeUserResource($this->whenLoaded('employeeUser')),
            'ip_whitelists' => IpWhitelistResource::collection($this->whenLoaded('ipWhitelists')),
        ];
    }
}
