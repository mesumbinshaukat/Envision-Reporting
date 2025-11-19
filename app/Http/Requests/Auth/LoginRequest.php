<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        /** @var \App\Services\EmployeeActivityLogger $activityLogger */
        $activityLogger = app(\App\Services\EmployeeActivityLogger::class);

        // Try admin login first
        if (Auth::guard('web')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // Try employee login
        if (Auth::guard('employee')->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());

            $employeeUser = Auth::guard('employee')->user();

            $activityLogger->log('auth_login_success', [
                'category' => 'auth',
                'summary' => 'Employee login successful',
                'description' => sprintf('Employee %s logged in successfully.', $employeeUser?->name ?? $this->input('email')),
                'employee_user' => $employeeUser,
                'metadata' => [
                    'email' => $this->input('email'),
                    'remember' => $this->boolean('remember'),
                ],
            ]);

            return;
        }

        // Both failed
        RateLimiter::hit($this->throttleKey());

        $employeeUser = \App\Models\EmployeeUser::where('email', $this->input('email'))->first();

        $activityLogger->log('auth_login_failed', [
            'category' => 'auth',
            'summary' => 'Employee login failed',
            'description' => 'Employee login attempt failed due to invalid credentials.',
            'employee_user' => $employeeUser,
            'allow_without_employee' => true,
            'metadata' => [
                'email' => $this->input('email'),
                'remember' => $this->boolean('remember'),
                'ip' => $this->ip(),
            ],
        ]);

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
