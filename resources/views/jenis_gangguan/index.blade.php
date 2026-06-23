@extends('layouts.admin')

@section('title', 'Manajemen Jenis Gangguan 110')
@section('page-title', 'Manajemen Jenis Gangguan 110')

@section('content')
<div x-data="{ openAddModal: false, openEditModal: false, editId: '', editNama: '' }">
    <div class="hover:bg-surface-800 p-6 rounded-xl border border-surface-600 shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-bold text-white mb-1">Daftar Jenis Gangguan</h3>
                <p class="text-sm text-gray-400">Kelola master data jenis gangguan untuk opsi pelaporan 110.</p>
            </div>
            <button @click="openAddModal = true" class="bg-blue-600 hover:bg-blue-500 text-white font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-blue-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Jenis Gangguan
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-surface-600 text-gray-400 text-sm">
                        <th class="py-3 px-4 font-medium">Nama Jenis Gangguan</th>
                        <th class="py-3 px-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jenisGangguans as $jg)
                    <tr class="border-b border-surface-600/50 hover:bg-surface-700/50 transition-colors">
                        <td class="py-3 px-4 text-white">{{ $jg->nama }}</td>
                        <td class="py-3 px-4 text-right">
                            <button @click="editId = '{{ $jg->id }}'; editNama = '{{ addslashes($jg->nama) }}'; openEditModal = true" 
                                    class="text-blue-400 hover:text-blue-300 p-2 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <form action="{{ route('jenis-gangguan.destroy', $jg->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus jenis gangguan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300 p-2 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                <p>Belum ada data jenis gangguan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $jenisGangguans->links() }}
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="openAddModal" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
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
            
            <div class="relative inline-block align-bottom hover:bg-surface-800 border border-surface-600 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                 x-show="openAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <form action="{{ route('jenis-gangguan.store') }}" method="POST">
                    @csrf
                    <div class="px-6 pt-5 pb-4">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-lg leading-6 font-medium text-white">Tambah Jenis Gangguan</h3>
                            <button type="button" @click="openAddModal = false" class="text-gray-400 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Nama Jenis Gangguan</label>
                                <input type="text" name="nama" required class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent" placeholder="Contoh: Kecelakaan Lalu Lintas">
                            </div>
                        </div>
                    </div>
                    <div class="bg-surface-900 px-6 py-4 flex justify-end gap-3 border-t border-surface-600">
                        <button type="button" @click="openAddModal = false" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-show="openEditModal" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity" aria-hidden="true"
                 x-show="openEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="openEditModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="relative inline-block align-bottom hover:bg-surface-800 border border-surface-600 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                 x-show="openEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <form :action="`/jenis-gangguan/${editId}`" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="px-6 pt-5 pb-4">
                        <div class="flex justify-between items-center mb-5">
                            <h3 class="text-lg leading-6 font-medium text-white">Edit Jenis Gangguan</h3>
                            <button type="button" @click="openEditModal = false" class="text-gray-400 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Nama Jenis Gangguan</label>
                                <input type="text" name="nama" x-model="editNama" required class="w-full bg-surface-900 border border-surface-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent">
                            </div>
                        </div>
                    </div>
                    <div class="bg-surface-900 px-6 py-4 flex justify-end gap-3 border-t border-surface-600">
                        <button type="button" @click="openEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
