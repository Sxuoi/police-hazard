<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSakerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');
        // Admin Polsek dilarang menembak endpoint pembuatan Saker
        if ($user && $user->type === 'POLSEK') {
            return false;
        }
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:sakers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'code' => ['required', 'string', 'max:20', 'unique:sakers,code'],
            'type' => ['sometimes', 'required', Rule::in(['MABES', 'POLDA', 'POLRESTABES', 'POLSEK'])],
            'parent_id' => ['nullable', 'exists:sakers,id'],
            'logo_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
