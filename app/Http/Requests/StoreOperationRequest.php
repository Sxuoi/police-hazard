<?php

namespace App\Http\Requests;

use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;

class StoreOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Operation::class)
            || in_array($this->user()->role, ['god_admin', 'saker_admin']);
    }

    public function rules(): array
    {
        return [
            'saker_id' => ['sometimes', 'uuid', 'exists:sakers,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'operation_type' => ['required', 'in:PH,PATROL'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'operation_type' => 'tipe operasi',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
        ];
    }
}
