<?php

namespace App\Http\Requests;

use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('operation'));
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'operation_type' => ['sometimes', 'required', 'string', 'in:PH,PATROL'],
            'start_time' => ['sometimes', 'required', 'string'],
            'end_time' => ['nullable', 'string'],
        ];

        if ($this->user() && $this->user()->isGodAdmin()) {
            $rules['saker_id'] = ['sometimes', 'required', 'exists:sakers,id'];
        }

        return $rules;
    }
}
