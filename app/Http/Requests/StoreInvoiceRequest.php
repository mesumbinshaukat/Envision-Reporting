<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Check if it's a one-time invoice or new client creation
        $isOneTime = $this->has('is_one_time') && $this->is_one_time;
        $isNewClient = $this->client_id === 'new_client';
        
        $rules = [
            'employee_id' => 'nullable|exists:employees,id',
            'currency_id' => 'required|exists:currencies,id',
            'status' => 'required|in:Pending,Partial Paid,Payment Done',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'special_note' => 'nullable|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'payment_method' => 'nullable|string|in:PayPal,Stripe,Bank,Venmo,CashApp,Other',
            'custom_payment_method' => 'required_if:payment_method,Other|nullable|string|max:255',
            'payment_processing_fee' => 'nullable|numeric|min:0',
        ];
        
        // Add paid_amount validation for Partial Paid status
        if ($this->status === 'Partial Paid') {
            $rules['paid_amount'] = 'required|numeric|min:0.01|lt:amount';
        }
        
        if ($isOneTime) {
            $rules['one_time_client_name'] = 'required|string|max:255';
        } elseif ($isNewClient) {
            $rules['new_client_name'] = 'required|string|max:255';
        } else {
            $rules['client_id'] = 'required|exists:clients,id';
        }
        
        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.mimes' => 'Attachments must be JPG, JPEG, PNG, or PDF files.',
            'attachments.*.max' => 'Each attachment must not exceed 2MB.',
            'payment_method.in' => 'Please select a valid payment method.',
            'custom_payment_method.required_if' => 'Please enter a custom payment method when "Other" is selected.',
            'payment_processing_fee.numeric' => 'Payment processing fee must be a valid number.',
        ];
    }
}
