<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'primary_contact' => $this->primary_contact,
            'secondary_contact' => $this->secondary_contact,
            'picture' => $this->picture,
            'picture_url' => $this->picture ? asset('storage/' . $this->picture) : null,
            'website' => $this->website,
            'created_by_employee_id' => $this->created_by_employee_id,
            'deleted_by_employee_id' => $this->deleted_by_employee_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relationships
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'created_by_employee' => new EmployeeUserResource($this->whenLoaded('createdByEmployee')),
            'deleted_by_employee' => new EmployeeUserResource($this->whenLoaded('deletedByEmployee')),
        ];
    }
}
