<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('web') && $this->user('web')->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_user_id' => ['required', 'exists:employee_users,id'],
            'attendance_date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'check_out' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:check_in'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedDateTimes());
    }

    private function normalizedDateTimes(): array
    {
        $fields = ['check_in', 'check_out'];
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (!$value) {
                continue;
            }

            $normalized[$field] = $this->normalizeDateTime($value) ?? $value;
        }

        return $normalized;
    }

    private function normalizeDateTime(string $value): ?string
    {
        $formats = [
            'Y-m-d\\TH:i',
            'Y-m-d\\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Try next format
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_user_id.required' => 'Please select an employee.',
            'employee_user_id.exists' => 'The selected employee does not exist.',
            'attendance_date.required' => 'Attendance date is required.',
            'attendance_date.date' => 'Please provide a valid date.',
            'check_in.date_format' => 'Check-in time must be in Y-m-d H:i:s format.',
            'check_out.date_format' => 'Check-out time must be in Y-m-d H:i:s format.',
            'check_out.after' => 'Check-out time must be after check-in time.',
        ];
    }
}
