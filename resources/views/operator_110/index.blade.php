@extends('layouts.admin')

@section('title', 'Daftar Laporan 110')
@section('page-title', 'Daftar Laporan 110')

@section('content')
<div x-data="{ 
    openAddModal: false,
    openEditModal: false,
    openDeleteModal: false,
    editForm: {},
    deleteForm: {},
    openEdit(report) {
        this.editForm = JSON.parse(JSON.stringify(report));
        if (this.editForm.waktu_kejadian) this.editForm.waktu_kejadian = this.editForm.waktu_kejadian.substring(0, 16);
        if (this.editForm.waktu_dilaporkan) this.editForm.waktu_dilaporkan = this.editForm.waktu_dilaporkan.substring(0, 16);
        this.openEditModal = true;
    },
    openDelete(report) {
        this.deleteForm = { id: report.id, no_tiketing: report.no_tiketing };
        this.openDeleteModal = true;
    }
}">
    <div class="bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-bold text-white mb-1">Daftar Laporan 110</h3>
                <p class="text-sm text-gray-400">Pantau tiket laporan masyarakat dan status penanganannya.</p>
            </div>
            <button @click="openAddModal = true" class="bg-blue-600 hover:bg-blue-500 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Laporan Baru
            </button>
        </div>
        
        @if(isset($reports))
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="border-b border-surface-600 text-gray-400 text-sm">
                        <th class="py-3 px-4 font-medium">No Tiketing</th>
                        <th class="py-3 px-4 font-medium">Waktu Dilaporkan</th>
                        <th class="py-3 px-4 font-medium">Jenis Gangguan</th>
                        <th class="py-3 px-4 font-medium">Status</th>
                        <th class="py-3 px-4 font-medium">Saker</th>
                        <th class="py-3 px-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                    <tr class="border-b border-surface-600/50 hover:bg-surface-700/50 transition-colors">
                        <td class="py-3 px-4 text-white font-medium">{{ $report->no_tiketing }}</td>
                        <td class="py-3 px-4 text-gray-300">{{ $report->waktu_dilaporkan ? $report->waktu_dilaporkan->format('d/m/Y H:i') : '-' }}</td>
                        <td class="py-3 px-4 text-gray-300 truncate max-w-[150px]" title="{{ $report->jenis_gangguan }}">{{ $report->jenis_gangguan }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded text-xs font-bold inline-flex items-center gap-1.5
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
                        </td>
                        <td class="py-3 px-4 text-gray-300">{{ $report->saker->name ?? '-' }}</td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('operator-110.show', $report->id) }}" class="inline-flex items-center justify-center text-blue-400 hover:text-blue-300 transition-colors bg-blue-400/10 hover:bg-blue-400/20 w-8 h-8 rounded-lg" title="Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @if($report->saker_id === auth()->user()->saker_id)
                                    @if($report->status === 'Sudah penanganan')
                                    <button type="button" @click="openEdit({{ $report->toJson() }})" class="inline-flex items-center justify-center text-amber-400 hover:text-amber-300 transition-colors bg-amber-400/10 hover:bg-amber-400/20 w-8 h-8 rounded-lg" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    @endif
                                <button type="button" @click="openDelete({{ collect($report->only(['id', 'no_tiketing']))->toJson() }})" class="inline-flex items-center justify-center text-red-400 hover:text-red-300 transition-colors bg-red-400/10 hover:bg-red-400/20 w-8 h-8 rounded-lg" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                <p>Belum ada laporan 110 masuk.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Tambah Laporan -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="openAddModal" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" aria-hidden="true"
                 x-show="openAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="openAddModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal Panel -->
            <div class="relative inline-block align-bottom bg-surface-800 border border-surface-600 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full"
                 x-show="openAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <form action="{{ route('operator-110.store') }}" method="POST">
                    @csrf
                    <div class="px-6 pt-5 pb-4">
                        <div class="flex justify-between items-center mb-5 border-b border-surface-600 pb-3">
                            <h3 class="text-lg leading-6 font-medium text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Buat Laporan Baru 110
                            </h3>
                            <button type="button" @click="openAddModal = false" class="text-gray-400 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Nomor Tiketing</label>
                                <input type="text" name="no_tiketing" required value="TKT-{{ time() }}" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Unit Armada Tugas</label>
                                <select name="unit_id" required class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent appearance-none">
                                    <option value="" disabled selected>Pilih Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->nama_unit }} ({{ $unit->no_wa }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-1">Jenis Gangguan</label>
                                <input type="text" name="jenis_gangguan" required placeholder="Contoh: Kecelakaan Lalu Lintas, Perkelahian..." class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                            </div>
                            <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Nama Pelapor</label>
                                    <input type="text" name="nama_pelapor" required placeholder="Nama lengkap..." class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">No HP Pelapor</label>
                                    <input type="text" name="no_hp_pelapor" required placeholder="Contoh: 0812..." class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Jenis No HP</label>
                                    <select name="jenis_no_hp_pelapor" required class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                                        <option value="WhatsApp">WhatsApp</option>
                                        <option value="Telepon Biasa">Telepon Biasa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-300 mb-1">Tempat Kejadian Perkara (TKP)</label>
                                <textarea name="tempat_kejadian" required rows="2" placeholder="Detail alamat lokasi..." class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Waktu Kejadian</label>
                                <input type="datetime-local" name="waktu_kejadian" required value="{{ date('Y-m-d\TH:i') }}" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent scheme-dark">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Waktu Dilaporkan</label>
                                <input type="datetime-local" name="waktu_dilaporkan" required value="{{ date('Y-m-d\TH:i') }}" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent scheme-dark">
                            </div>
                        </div>
                    </div>
                    <div class="bg-surface-900 px-6 py-4 flex justify-end gap-3 border-t border-surface-600">
                        <button type="button" @click="openAddModal = false" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            Kirim ke Pamapta
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Laporan -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="openEditModal" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" aria-hidden="true" x-show="openEditModal" @click="openEditModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="relative inline-block align-bottom bg-surface-800 border border-surface-600 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full" x-show="openEditModal">
                <form :action="`{{ url('operator-110') }}/${editForm.id}`" method="POST" enctype="multipart/form-data">
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
                                <input type="text" name="jenis_gangguan" required x-model="editForm.jenis_gangguan" class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
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
                <form :action="`{{ url('operator-110') }}/${deleteForm.id}`" method="POST">
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
</div>
@endsection
