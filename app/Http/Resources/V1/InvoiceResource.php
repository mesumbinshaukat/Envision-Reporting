<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'client_id' => $this->client_id,
            'employee_id' => $this->employee_id,
            'currency_id' => $this->currency_id,
            'exchange_rate_at_time' => $this->exchange_rate_at_time,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'created_by_employee_id' => $this->created_by_employee_id,
            'approved_at' => $this->approved_at?->toISOString(),
            'approved_by' => $this->approved_by,
            'due_date' => $this->due_date?->toDateString(),
            'amount' => $this->amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_month' => $this->payment_month,
            'tax' => $this->tax,
            'special_note' => $this->special_note,
            'commission_paid' => $this->commission_paid,
            'is_one_time' => $this->is_one_time,
            'one_time_client_name' => $this->one_time_client_name,
            'client_name' => $this->client_name,
            'attachments' => $this->attachments,
            'payment_method' => $this->payment_method,
            'custom_payment_method' => $this->custom_payment_method,
            'payment_processing_fee' => $this->payment_processing_fee,
            'commission' => $this->calculateCommission(),
            'amount_in_base_currency' => $this->getAmountInBaseCurrency(),
            'paid_amount_in_base_currency' => $this->getPaidAmountInBaseCurrency(),
            'remaining_amount_in_base_currency' => $this->getRemainingAmountInBaseCurrency(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relationships
            'client' => new ClientResource($this->whenLoaded('client')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'milestones' => InvoiceMilestoneResource::collection($this->whenLoaded('milestones')),
            'created_by_employee' => new EmployeeUserResource($this->whenLoaded('createdByEmployee')),
            'approved_by_user' => new UserResource($this->whenLoaded('approvedBy')),
        ];
    }
}
