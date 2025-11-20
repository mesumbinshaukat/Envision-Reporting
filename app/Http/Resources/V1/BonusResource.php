<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_id' => $this->employee_id,
            'currency_id' => $this->currency_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date?->toDateString(),
            'release_type' => $this->release_type,
            'released' => $this->released,
            'exchange_rate_at_time' => $this->exchange_rate_at_time,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
        ];
    }
}
