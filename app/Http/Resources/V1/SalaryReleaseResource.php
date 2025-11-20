<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_id' => $this->employee_id,
            'currency_id' => $this->currency_id,
            'month' => $this->month,
            'year' => $this->year,
            'basic_salary' => $this->basic_salary,
            'bonus' => $this->bonus,
            'commission' => $this->commission,
            'deductions' => $this->deductions,
            'net_salary' => $this->net_salary,
            'release_date' => $this->release_date?->toDateString(),
            'notes' => $this->notes,
            'exchange_rate_at_time' => $this->exchange_rate_at_time,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
        ];
    }
}
