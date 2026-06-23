<?php

namespace App\Models;

use App\Models\Concerns\HasUuidV7;
use App\Models\Concerns\SakerScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report110 Model — PRD §7.
 * Represents emergency reports created by dispatchers and completed by field responders.
 * Scoped by Saker to restrict visibility to the Saker hierarchy.
 */
#[ScopedBy([SakerScope::class])]
class Report110 extends Model
{
    use HasUuidV7;

    protected $table = 'reports_110';

    protected $fillable = [
        'saker_id',
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
        return $this->belongsTo(Unit::class, 'unit_id')->withTrashed();
    }

    /**
     * Get the Saker responsible for this report.
     */
    public function saker(): BelongsTo
    {
        return $this->belongsTo(Saker::class, 'saker_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($report) {
            if ($report->bukti_foto_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($report->bukti_foto_path);
            }
        });

        static::updating(function ($report) {
            if ($report->isDirty('bukti_foto_path') && $report->getOriginal('bukti_foto_path')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($report->getOriginal('bukti_foto_path'));
            }
        });
    }
}
