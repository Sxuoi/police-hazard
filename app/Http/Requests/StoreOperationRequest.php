<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Operation::class)
            || in_array($this->user()->role, ['god_admin', 'saker_admin']);
    }

    public function rules(): array
    {
        return [
            'saker_id'       => ['sometimes', 'uuid', 'exists:sakers,id'],
            'name'           => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:500'],
            'operation_type' => ['required', 'in:PH,PATROL'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['nullable', 'date', 'after:start_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'operation_type' => 'tipe operasi',
            'start_date'     => 'tanggal mulai',
            'end_date'       => 'tanggal selesai',
        ];
    }
}
