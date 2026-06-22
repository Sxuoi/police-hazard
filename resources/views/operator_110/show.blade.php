@extends('layouts.admin')

@section('title', 'Detail Laporan 110: ' . $report->no_tiketing)
@section('page-title', 'Detail Laporan 110')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    openEditModal: false,
    openDeleteModal: false,
    editForm: {
        id: '{{ $report->id }}',
        no_tiketing: '{{ $report->no_tiketing }}',
        unit_id: '{{ $report->unit_id }}',
        jenis_gangguan: '{{ addslashes($report->jenis_gangguan) }}',
        nama_pelapor: '{{ addslashes($report->nama_pelapor) }}',
        no_hp_pelapor: '{{ addslashes($report->no_hp_pelapor) }}',
        jenis_no_hp_pelapor: '{{ $report->jenis_no_hp_pelapor }}',
        tempat_kejadian: '{{ addslashes($report->tempat_kejadian) }}',
        waktu_kejadian: '{{ $report->waktu_kejadian ? $report->waktu_kejadian->format('Y-m-d\TH:i') : '' }}',
        waktu_dilaporkan: '{{ $report->waktu_dilaporkan ? $report->waktu_dilaporkan->format('Y-m-d\TH:i') : '' }}',
        nama_pamapta: '{{ addslashes($report->nama_pamapta) }}',
        nrp_pamapta: '{{ addslashes($report->nrp_pamapta) }}',
        modus_operandi: '{{ addslashes($report->modus_operandi) }}',
        korban: '{{ addslashes($report->korban) }}',
        uraian_kejadian: '{{ addslashes($report->uraian_kejadian) }}',
        pelaku: '{{ addslashes($report->pelaku) }}',
        sanksi_sanksi: '{{ addslashes($report->sanksi_sanksi) }}',
        motif: '{{ addslashes($report->motif) }}',
        alat_yang_digunakan: '{{ addslashes($report->alat_yang_digunakan) }}',
        kerugian: '{{ addslashes($report->kerugian) }}',
        bukti_yang_dapat_disita: '{{ addslashes($report->bukti_yang_dapat_disita) }}',
        tindakan_kepolisian: '{{ addslashes($report->tindakan_kepolisian) }}',
        keterangan_lain: '{{ addslashes($report->keterangan_lain) }}'
    },
    deleteForm: { id: '{{ $report->id }}', no_tiketing: '{{ $report->no_tiketing }}' }
}">
    <!-- Header Card -->
    <div class="bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h3 class="text-2xl font-bold text-white">{{ $report->no_tiketing }}</h3>
                <span class="px-2.5 py-1 rounded-md text-xs font-bold inline-flex items-center gap-1.5
                    {{ $report->status === 'Butuh penanganan' ? 'bg-red-500/20 text-red-400' : '' }}
                    {{ $report->status === 'Sedang penanganan' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                    {{ $report->status === 'Sudah penanganan' ? 'bg-green-500/20 text-green-400' : '' }}">
                    @if($report->status === 'Butuh penanganan')
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                    @elseif($report->status === 'Sedang penanganan')
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                    @else
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                    @endif
                    {{ $report->status }}
                </span>
            </div>
            <p class="text-gray-400 text-sm">Dilaporkan pada: {{ $report->waktu_dilaporkan ? $report->waktu_dilaporkan->format('d F Y H:i') : '-' }} | Pembuat: {{ $report->saker->name ?? '-' }}</p>
        </div>
        
        <div class="flex flex-col items-end gap-2">
            <div class="flex flex-wrap gap-2 justify-end">
                <!-- Tombol Kembali -->
                <a href="{{ route('operator-110.index') }}" class="inline-flex items-center justify-center gap-2 bg-surface-600 hover:bg-surface-500 text-white px-4 py-2.5 rounded-lg font-semibold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </a>

                @if($report->saker_id === auth()->user()->saker_id)
                    <!-- Tombol Edit (Hanya jika status Sudah penanganan) -->
                    @if($report->status === 'Sudah penanganan')
                        <button type="button" @click="openEditModal = true" class="inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-500 text-white px-4 py-2.5 rounded-lg font-semibold transition-all shadow-lg shadow-amber-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit
                        </button>
                    @endif

                    <!-- Tombol Hapus -->
                    <button type="button" @click="openDeleteModal = true" class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-500 text-white px-4 py-2.5 rounded-lg font-semibold transition-all shadow-lg shadow-red-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>

                    <!-- Tombol Teruskan ke WhatsApp -->
                    <a href="{{ $waLink ?? '#' }}" target="_blank" class="inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#128C7E] text-white px-4 py-2.5 rounded-lg font-semibold transition-all shadow-lg shadow-[#25D366]/20">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        Teruskan
                    </a>
                @endif
            </div>
            @if($report->saker_id === auth()->user()->saker_id)
                <p class="text-xs text-gray-500">Via WhatsApp (+{{ preg_replace('/[^0-9]/', '', $report->unit->no_wa ?? '') }})</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informasi Detail -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-surface-600 pb-2">Informasi Laporan</h4>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Nama Pelapor</p>
                            <p class="text-white font-medium bg-surface-900 p-3 rounded-lg">{{ $report->nama_pelapor ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">No HP Pelapor</p>
                            <p class="text-white font-medium bg-surface-900 p-3 rounded-lg">
                                {{ $report->no_hp_pelapor ?? '-' }} 
                                @if($report->jenis_no_hp_pelapor)
                                    <span class="text-xs text-blue-400">({{ $report->jenis_no_hp_pelapor }})</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Jenis Gangguan</p>
                        <p class="text-white font-medium bg-surface-900 p-3 rounded-lg">{{ $report->jenis_gangguan }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Waktu Kejadian</p>
                            <p class="text-white font-medium bg-surface-900 p-3 rounded-lg">{{ $report->waktu_kejadian ? $report->waktu_kejadian->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Waktu Mendatangi TKP</p>
                            <p class="text-white font-medium bg-surface-900 p-3 rounded-lg">{{ $report->waktu_mendatangi_tkp ? $report->waktu_mendatangi_tkp->format('d/m/Y H:i:s') : 'Belum mendatangi' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Tempat Kejadian Perkara (TKP) - Deskripsi</p>
                        <p class="text-white font-medium bg-surface-900 p-3 rounded-lg">{{ $report->tempat_kejadian }}</p>
                    </div>
                </div>
            </div>

            <!-- Dokumentasi Hasil Penanganan -->
            <div class="bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4 border-b border-surface-600 pb-3">
                    <h4 class="text-white font-bold">Hasil Penanganan Pamapta</h4>
                    @if($report->status === 'Sedang penanganan')
                        <span class="text-xs bg-yellow-500/20 text-yellow-400 px-3 py-1.5 rounded-md border border-yellow-500/30 font-medium inline-flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                            Draft tersimpan: {{ $report->updated_at ? $report->updated_at->format('d/m/Y H:i') : '-' }}
                        </span>
                    @endif
                </div>
                
                @if(in_array($report->status, ['Sedang penanganan', 'Sudah penanganan']))
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="col-span-full border-b border-surface-600 pb-2 mb-2">
                                <p class="text-sm text-gray-500 mb-1">Petugas Pamapta</p>
                                <p class="text-white font-medium">{{ $report->nama_pamapta ?? '-' }} (NRP: {{ $report->nrp_pamapta ?? '-' }})</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Uraian Kejadian</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->uraian_kejadian ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Tindakan Kepolisian</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->tindakan_kepolisian ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Modus Operandi</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->modus_operandi ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Korban</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->korban ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Pelaku</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->pelaku ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Sanksi-sanksi</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->sanksi_sanksi ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Motif</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->motif ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Alat yang Digunakan</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->alat_yang_digunakan ?? '-' }}</p>
                            </div>

                            <div>
                                <p class="text-sm text-gray-500 mb-1">Kerugian</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->kerugian ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Bukti yang Dapat Disita</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->bukti_yang_dapat_disita ?? '-' }}</p>
                            </div>

                            <div class="col-span-full">
                                <p class="text-sm text-gray-500 mb-1">Keterangan Lain</p>
                                <p class="text-white font-medium whitespace-pre-wrap">{{ $report->keterangan_lain ?? '-' }}</p>
                            </div>
                        </div>
                        
                        @if($report->bukti_foto_path)
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-2">Foto Dokumentasi (Watermarked)</p>
                                <a href="{{ asset('storage/' . $report->bukti_foto_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $report->bukti_foto_path) }}" alt="Foto Penanganan" class="rounded-lg w-full max-h-96 object-cover border border-surface-600 hover:opacity-90 transition cursor-pointer">
                                </a>
                            </div>
                        @else
                            <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-6 flex flex-col items-center justify-center text-gray-500">
                                <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p>Tidak ada foto yang dilampirkan.</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex items-center justify-center h-32 bg-surface-900 rounded-lg border border-dashed border-surface-600">
                        <p class="text-gray-500 text-sm">Petugas belum mendatangi TKP (status: Butuh penanganan).</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Map -->
        <div class="space-y-6">
            <div class="bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-surface-600 pb-2">Lokasi Terkini</h4>
                
                @if($report->lat && $report->lng)
                    <div class="rounded-lg overflow-hidden border border-surface-600 h-64 relative z-0">
                        <div id="map" class="absolute inset-0"></div>
                    </div>
                    <div class="mt-3 flex gap-2 text-xs text-gray-400">
                        <span class="bg-surface-900 px-2 py-1 rounded">Lat: {{ number_format($report->lat, 6) }}</span>
                        <span class="bg-surface-900 px-2 py-1 rounded">Lng: {{ number_format($report->lng, 6) }}</span>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-surface-600">
                        <p class="text-xs text-gray-500 mb-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Alamat Aktual Kedatangan
                        </p>
                        <p class="text-sm text-white font-medium bg-surface-900 p-3 rounded-lg border border-surface-600 leading-relaxed">
                            {{ $report->alamat_aktual_110 ?? 'Nama alamat tidak tersedia' }}
                        </p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-64 bg-surface-900 rounded-lg border border-dashed border-surface-600 text-gray-500">
                        <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm">Lokasi belum dikirim oleh petugas.</p>
                    </div>
                @endif
            </div>
            
            <div class="bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm">
                <h4 class="text-white font-bold mb-4 border-b border-surface-600 pb-2">Status Link Akses Pamapta</h4>
                <div class="bg-surface-900 p-3 rounded-lg break-all">
                    <p class="text-xs text-blue-400 font-mono">{{ route('pamapta.report.show', ['token' => $report->token]) }}</p>
            </div>
        </div>

        @if($report->saker_id === auth()->user()->saker_id)
            <!-- Modal Edit Laporan -->
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="openEditModal" x-cloak>
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" aria-hidden="true" x-show="openEditModal" @click="openEditModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="relative inline-block align-bottom bg-surface-800 border border-surface-600 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full" x-show="openEditModal">
                        <form action="{{ route('operator-110.update', $report->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="px-6 pt-5 pb-4">
                                <div class="flex justify-between items-center mb-5 border-b border-surface-600 pb-3">
                                    <h3 class="text-lg leading-6 font-medium text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Edit Laporan 110
                                    </h3>
                                    <button type="button" @click="openEditModal = false" class="text-gray-400 hover:text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Nomor Tiketing</label>
                                        <input type="text" name="no_tiketing" required x-model="editForm.no_tiketing" readonly class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-gray-400 opacity-70 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Unit Armada Tugas</label>
                                        <select name="unit_id" required x-model="editForm.unit_id" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent appearance-none">
                                            <option value="" disabled>Pilih Unit</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->nama_unit }} ({{ $unit->no_wa }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Jenis Gangguan</label>
                                        <select name="jenis_gangguan" required x-model="editForm.jenis_gangguan" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent appearance-none">
                                            <option value="" disabled>Pilih Jenis Gangguan</option>
                                            @foreach($jenisGangguans as $jg)
                                                <option value="{{ $jg->nama }}">{{ $jg->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Nama Pelapor</label>
                                            <input type="text" name="nama_pelapor" required x-model="editForm.nama_pelapor" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">No HP Pelapor</label>
                                            <input type="text" name="no_hp_pelapor" required x-model="editForm.no_hp_pelapor" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Jenis No HP</label>
                                            <select name="jenis_no_hp_pelapor" required x-model="editForm.jenis_no_hp_pelapor" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                                <option value="WhatsApp">WhatsApp</option>
                                                <option value="Telepon Biasa">Telepon Biasa</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Tempat Kejadian Perkara (TKP)</label>
                                        <textarea name="tempat_kejadian" required rows="2" x-model="editForm.tempat_kejadian" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Waktu Kejadian</label>
                                        <input type="datetime-local" name="waktu_kejadian" required x-model="editForm.waktu_kejadian" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent scheme-dark">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Waktu Dilaporkan</label>
                                        <input type="datetime-local" name="waktu_dilaporkan" required x-model="editForm.waktu_dilaporkan" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent scheme-dark">
                                    </div>
                                </div>

                                <!-- Tab / Pembatas Data Pamapta -->
                                <div class="mt-8 mb-4">
                                    <h4 class="text-md font-semibold text-blue-400 border-b border-surface-600 pb-2 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        Hasil Penanganan (Pamapta)
                                    </h4>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Nama Pamapta</label>
                                        <input type="text" name="nama_pamapta" x-model="editForm.nama_pamapta" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">NRP Pamapta</label>
                                        <input type="text" name="nrp_pamapta" x-model="editForm.nrp_pamapta" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Uraian Kejadian</label>
                                        <textarea name="uraian_kejadian" rows="2" x-model="editForm.uraian_kejadian" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Modus Operandi</label>
                                        <input type="text" name="modus_operandi" x-model="editForm.modus_operandi" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Motif</label>
                                        <input type="text" name="motif" x-model="editForm.motif" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Korban</label>
                                        <input type="text" name="korban" x-model="editForm.korban" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Pelaku</label>
                                        <input type="text" name="pelaku" x-model="editForm.pelaku" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Alat Yang Digunakan</label>
                                        <input type="text" name="alat_yang_digunakan" x-model="editForm.alat_yang_digunakan" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Barang Bukti</label>
                                        <input type="text" name="bukti_yang_dapat_disita" x-model="editForm.bukti_yang_dapat_disita" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Kerugian</label>
                                        <input type="text" name="kerugian" x-model="editForm.kerugian" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Sanksi-sanksi</label>
                                        <input type="text" name="sanksi_sanksi" x-model="editForm.sanksi_sanksi" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Tindakan Kepolisian</label>
                                        <textarea name="tindakan_kepolisian" rows="2" x-model="editForm.tindakan_kepolisian" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"></textarea>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Keterangan Lain</label>
                                        <textarea name="keterangan_lain" rows="2" x-model="editForm.keterangan_lain" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"></textarea>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Perbarui Foto Dokumentasi (Opsional)</label>
                                        <input type="file" name="foto" accept="image/*" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent file:text-white hover:file:bg-blue-600 transition-colors">
                                        <p class="text-xs text-gray-400 mt-1">Hanya unggah jika ingin mengubah foto. Watermark historis akan otomatis ditempelkan pada foto baru.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-surface-900 px-6 py-4 flex justify-end gap-3 border-t border-surface-600">
                                <button type="button" @click="openEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Hapus Laporan -->
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="openDeleteModal" x-cloak>
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" aria-hidden="true" x-show="openDeleteModal" @click="openDeleteModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="relative inline-block align-bottom bg-surface-800 border border-surface-600 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full" x-show="openDeleteModal">
                        <form action="{{ route('operator-110.destroy', $report->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="px-6 pt-5 pb-4">
                                <div class="flex flex-col items-center text-center mb-5 border-b border-surface-600 pb-4">
                                    <div class="w-12 h-12 rounded-full bg-red-500/20 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <h3 class="text-lg leading-6 font-medium text-white mb-1">Hapus Laporan?</h3>
                                    <p class="text-sm text-gray-400">Tindakan ini tidak dapat dibatalkan. Masukkan Kode Tiket untuk mengonfirmasi penghapusan.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1 text-center">Kode Tiket: <span x-text="deleteForm.no_tiketing" class="font-bold text-white"></span></label>
                                    <input type="text" name="kode_tiket" required placeholder="Ketik ulang kode tiket..." class="w-full text-center bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                                </div>
                            </div>
                            <div class="bg-surface-900 px-6 py-4 flex justify-end gap-3 border-t border-surface-600">
                                <button type="button" @click="openDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors w-full sm:w-auto">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-medium rounded-lg transition-colors w-full sm:w-auto">
                                    Konfirmasi Hapus
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if($report->lat && $report->lng)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = {{ $report->lat }};
        const lng = {{ $report->lng }};
        const title = "{{ addslashes($report->no_tiketing) }}";

        const map = L.map('map').setView([lat, lng], 16);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(`<b>${title}</b><br>Lokasi Terkini Petugas`).openPopup();
    });
</script>
@endif
@endpush
