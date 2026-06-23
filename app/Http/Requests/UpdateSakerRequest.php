<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSakerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');
        // Admin Polsek dilarang update Saker
        if ($user && $user->type === 'POLSEK') {
            return false;
        }
        return true;
    }

    public function rules(): array
    {
        $sakerId = $this->route('saker');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('sakers')->ignore($sakerId)],
            'password' => ['nullable', 'string', 'min:8'],
            'code' => ['required', 'string', 'max:20', Rule::unique('sakers')->ignore($sakerId)],
            'type' => ['sometimes', 'required', Rule::in(['MABES', 'POLDA', 'POLRESTABES', 'POLSEK'])],
            'parent_id' => ['nullable', 'exists:sakers,id'],
            'logo_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
