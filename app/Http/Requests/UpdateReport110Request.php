<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReport110Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // $this->route('report') could be the ID or the Report110 model depending on routing.
        $reportId = $this->route('report')?->id ?? $this->route('report');

        return [
            'no_tiketing' => ['required', 'string', 'max:255', 'unique:reports_110,no_tiketing,' . $reportId],
            'jenis_gangguan' => ['required', 'string', 'max:255'],
            'waktu_kejadian' => ['required', 'date'],
            'waktu_dilaporkan' => ['required', 'date'],
            'nama_pelapor' => ['required', 'string', 'max:150'],
            'no_hp_pelapor' => ['required', 'string', 'max:20'],
            'jenis_no_hp_pelapor' => ['required', 'in:WhatsApp,Telepon Biasa'],
            'tempat_kejadian' => ['required', 'string', 'max:500'],
            'unit_id' => ['required', 'uuid', 'exists:units,id'],
            
            // Pamapta Fields (semua nullable karena mungkin diedit saat belum/sudah selesai)
            'nama_pamapta' => ['nullable', 'string', 'max:255'],
            'nrp_pamapta' => ['nullable', 'string', 'max:255'],
            'modus_operandi' => ['nullable', 'string'],
            'korban' => ['nullable', 'string'],
            'uraian_kejadian' => ['nullable', 'string'],
            'pelaku' => ['nullable', 'string'],
            'sanksi_sanksi' => ['nullable', 'string'],
            'motif' => ['nullable', 'string'],
            'alat_yang_digunakan' => ['nullable', 'string'],
            'kerugian' => ['nullable', 'string'],
            'bukti_yang_dapat_disita' => ['nullable', 'string'],
            'tindakan_kepolisian' => ['nullable', 'string'],
            'keterangan_lain' => ['nullable', 'string'],
            
            // Foto untuk diupdate oleh operator
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ];
    }
}
