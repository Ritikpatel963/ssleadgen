<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Throws a ValidationException on too many attempts.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Increment the login attempt counter for rate limiting.
     */
    public function incrementLoginAttempts(): void
    {
        RateLimiter::hit($this->throttleKey(), 60);
    }

    /**
     * Clear the rate limiter on successful login.
     */
    public function clearLoginAttempts(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Get the rate limiter key: sha1(email|ip).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
