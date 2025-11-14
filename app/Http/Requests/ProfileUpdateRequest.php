<?php

namespace App\Http\Requests;

use App\Models\EmployeeUser;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isEmployee = auth()->guard('employee')->check();
        $currentUser = $isEmployee ? auth()->guard('employee')->user() : $this->user();

        $emailUniqueRule = $isEmployee
            ? Rule::unique('employee_users', 'email')->ignore($currentUser?->id)
            : Rule::unique(User::class)->ignore($currentUser?->id);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                $emailUniqueRule,
            ],
            'profile_photo' => ['nullable', 'file', 'mimes:png,jpeg,jpg,svg,webp', 'max:5120'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_photo.mimes' => 'Only PNG, JPEG, JPG, SVG, or WEBP images are allowed.',
            'profile_photo.max' => 'Profile photos may not be greater than 5 MB.',
        ];
    }
}
