<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report110 Model — PRD §7.
 * Represents emergency reports created by dispatchers and completed by field responders.
 * Bypasses row-level tenancy (SakerScope is explicitly not loaded) for global monitoring.
 */
class Report110 extends Model
{
    use HasUuidV7;

    protected $table = 'reports_110';

    protected $fillable = [
        'no_tiketing',
        'unit_id',
        'token',
        'status',
        'alamat_aktual_110',
        'jenis_gangguan',
        'waktu_kejadian',
        'waktu_dilaporkan',
        'nama_pelapor',
        'no_hp_pelapor',
        'jenis_no_hp_pelapor',
        'waktu_mendatangi_tkp',
        'tempat_kejadian',
        'nama_pamapta',
        'nrp_pamapta',
        'modus_operandi',
        'korban',
        'uraian_kejadian',
        'pelaku',
        'sanksi_sanksi',
        'motif',
        'alat_yang_digunakan',
        'kerugian',
        'bukti_yang_dapat_disita',
        'tindakan_kepolisian',
        'keterangan_lain',
        'bukti_foto_path',
        'koordinat_110',
        'waktu_diselesaikan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kejadian'       => 'datetime',
            'waktu_dilaporkan'     => 'datetime',
            'waktu_mendatangi_tkp' => 'datetime',
            'waktu_diselesaikan'   => 'datetime',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
        ];
    }

    /**
     * Get the unit responsible for this report.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
