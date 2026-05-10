<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * PRD §13.1 — 5 attempts per 15 min per IP+NRP combination.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nrp'      => ['required', 'string', 'max:20'],
            'password'  => ['required', 'string'],
        ];
    }

    /**
     * Enforce rate limiting before authentication.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = $this->throttleKey();
        $maxAttempts = config('policehazard.auth.max_login_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            throw ValidationException::withMessages([
                'nrp' => [__("Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.")],
            ]);
        }
    }

    public function hitRateLimiter(): void
    {
        RateLimiter::hit(
            $this->throttleKey(),
            config('policehazard.auth.lockout_minutes', 15) * 60
        );
    }

    public function clearRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->input('nrp')) . '|' . $this->ip()
        );
    }
}
