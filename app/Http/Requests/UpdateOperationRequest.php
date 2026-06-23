<?php

namespace App\Http\Requests;

use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operation = $this->route('operation');

        if (is_string($operation)) {
            $operation = Operation::find($operation);
        }

        if (! $operation) {
            return false;
        }

        return $this->user()->can('update', $operation);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $operation = $this->route('operation');

            if (is_string($operation)) {
                $operation = Operation::find($operation);
            }

            if ($operation && in_array($operation->status, ['completed', 'archived'], true)) {
                $validator->errors()->add('status', 'Operasi yang telah selesai atau diarsipkan tidak dapat diubah.');
            }
        });
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
