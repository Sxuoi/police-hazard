<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReport110Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_tiketing' => ['required', 'string', 'max:255', 'unique:reports_110,no_tiketing'],
            'jenis_gangguan' => ['required', 'string', 'max:255'],
            'waktu_kejadian' => ['required', 'date'],
            'waktu_dilaporkan' => ['required', 'date'],
            'nama_pelapor' => ['required', 'string', 'max:150'],
            'no_hp_pelapor' => ['required', 'string', 'max:20'],
            'jenis_no_hp_pelapor' => ['required', 'in:WhatsApp,Telepon Biasa'],
            'tempat_kejadian' => ['required', 'string', 'max:500'],
            'unit_id' => ['required', 'uuid', 'exists:units,id'],
        ];
    }
}
