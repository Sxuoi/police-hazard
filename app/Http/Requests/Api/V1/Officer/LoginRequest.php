<?php

namespace App\Http\Requests\Api\V1\Officer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Officer API — Login request (R1.1).
 * Validates nrp + password. Rate limiting is applied via the
 * `throttle:officer-login` middleware on the route.
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
            'nrp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ];
    }
}
