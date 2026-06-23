<?php

namespace App\Http\Requests;

use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;

class StoreOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Operation::class);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'operation_type' => ['required', 'string', 'in:PH,PATROL'],
            'start_time' => ['required', 'string'],
            'end_time' => ['nullable', 'string'],
        ];

        if ($this->user() && $this->user()->isGodAdmin()) {
            $rules['saker_id'] = ['required', 'exists:sakers,id'];
        }

        return $rules;
    }
}
