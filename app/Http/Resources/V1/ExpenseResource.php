<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'currency_id' => $this->currency_id,
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date?->toDateString(),
            'exchange_rate_at_time' => $this->exchange_rate_at_time,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
        ];
    }
}
