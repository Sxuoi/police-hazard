@extends('layouts.admin')

@section('title', 'Buat Penugasan')
@section('page-title', 'Wizard Penugasan Petugas')

@section('content')
<div class="max-w-4xl mx-auto" x-data="assignmentWizard()">
    
    @if($errors->any())
        <div class="mb-6">
            <x-alert type="error">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        </div>
    @endif

    <form method="POST" action="{{ route('assignments.store') }}" id="assignmentForm">
        @csrf

        {{-- Progress Bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-[var(--color-surface-600)] z-0 rounded-full"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[var(--color-accent)] z-0 rounded-full transition-all duration-300" :style="'width: ' + ((step - 1) / 2 * 100) + '%'"></div>
                
                <template x-for="s in 3">
                    <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full border-4 border-[var(--color-surface-800)] font-bold transition-colors"
                         :class="step >= s ? 'bg-[var(--color-accent)] text-white' : 'bg-[var(--color-surface-600)] text-gray-400'">
                        <span x-text="s"></span>
                    </div>
                </template>
            </div>
            <div class="flex justify-between mt-2 text-xs font-medium text-gray-400">
                <span>Lokasi & Waktu</span>
                <span>Petugas</span>
                <span>Konfirmasi</span>
            </div>
        </div>

        {{-- STEP 1: Lokasi & Waktu --}}
        <div x-show="step === 1" x-transition.opacity.duration.300ms class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6 space-y-5">
            <h3 class="text-xl font-bold text-white mb-4">Pilih Lokasi & Waktu Penugasan</h3>
            
            @if(auth()->user()->isGodAdmin())
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Satuan Kerja Penugasan</label>
                    <select name="assigned_saker_id" class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                        @foreach($sakers as $saker)
                            <option value="{{ $saker->id }}">{{ $saker->code }} — {{ $saker->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="assigned_saker_id" value="{{ auth()->user()->saker_id }}">
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Operasi</label>
                <select name="operation_id" x-model="operation_id" @change="onOperationChange" required class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                    <option value="">-- Pilih Operasi --</option>
                    @foreach($operations as $op)
                        <option
                            value="{{ $op->id }}"
                            data-start="{{ \Illuminate\Support\Str::substr($op->start_time, 0, 5) }}"
                            data-end="{{ $op->end_time ? \Illuminate\Support\Str::substr($op->end_time, 0, 5) : '' }}"
                        >{{ $op->name }}</option>
                    @endforeach
                </select>
                <template x-if="operationStart">
                    <div class="mt-2 text-xs text-gray-400 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Waktu Operasi: <span class="text-white font-medium" x-text="operationStart + (operationEnd ? ' – ' + operationEnd : '')"></span></span>
                    </div>
                </template>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Zona <span x-show="isLoadingZones" class="text-xs text-[var(--color-accent)] animate-pulse ml-2">Loading...</span></label>
                <select x-model="zone_id" @change="fetchLocations" :disabled="!operation_id || isLoadingZones" class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white disabled:opacity-50">
                    <option value="">-- Pilih Zona --</option>
                    <template x-for="zone in zones" :key="zone.id">
                        <option :value="zone.id" x-text="zone.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Lokasi <span x-show="isLoadingLocations" class="text-xs text-[var(--color-accent)] animate-pulse ml-2">Loading...</span></label>
                <select name="location_id" x-model="location_id" required :disabled="!zone_id || isLoadingLocations" class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white disabled:opacity-50">
                    <option value="">-- Pilih Lokasi --</option>
                    <template x-for="loc in locations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.name"></option>
                    </template>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal Mulai (Start Date)</label>
                    <input type="date" name="start_date" x-model="start_date" required class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Tanggal Selesai (End Date - Opsional)</label>
                    <input type="date" name="end_date" x-model="end_date" class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                    <span class="text-xs text-gray-500 mt-1 block">Kosongkan untuk penugasan berkelanjutan (selamanya)</span>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button" @click="if(canProceedToStep2) step = 2" :disabled="!canProceedToStep2" class="px-6 py-3 bg-[var(--color-accent)] hover:bg-blue-600 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Selanjutnya &rarr;
                </button>
            </div>
        </div>

        {{-- STEP 2: Petugas --}}
        <div x-show="step === 2" style="display: none;" x-transition.opacity.duration.300ms class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6 space-y-5">
            <h3 class="text-xl font-bold text-white mb-4">Pilih Petugas</h3>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Cari Petugas (NRP / Nama)</label>
                <div class="relative">
                    <input type="text" x-model="searchQuery" @input.debounce.300ms="searchOfficers" placeholder="Ketik nama atau NRP..." class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white">
                    <div x-show="isSearching" class="absolute right-4 top-3 text-[var(--color-accent)]">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                
                {{-- Search Results --}}
                <div x-show="searchResults.length > 0 && searchQuery.length > 0" class="mt-2 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl overflow-hidden max-h-60 overflow-y-auto">
                    <template x-for="officer in searchResults" :key="officer.id">
                        <div @click="addOfficer(officer)" class="p-3 border-b border-[var(--color-surface-600)] hover:bg-[var(--color-surface-600)] cursor-pointer flex justify-between items-center transition-colors">
                            <div>
                                <div class="font-medium text-white" x-text="officer.name"></div>
                                <div class="text-xs text-gray-400" x-text="officer.nrp"></div>
                            </div>
                            <button type="button" class="text-xs bg-[var(--color-surface-800)] px-2 py-1 rounded border border-[var(--color-surface-500)] text-white">Pilih</button>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-300 mb-2">Petugas Terpilih (<span x-text="selectedOfficers.length"></span>)</h4>
                <div class="space-y-2">
                    <template x-for="(officer, index) in selectedOfficers" :key="officer.id">
                        <div class="flex items-center justify-between bg-[var(--color-surface-700)] p-3 rounded-xl border border-[var(--color-surface-600)]">
                            <div>
                                <div class="font-medium text-white" x-text="officer.name"></div>
                                <div class="text-xs text-gray-400" x-text="officer.nrp"></div>
                                <input type="hidden" name="officer_ids[]" :value="officer.id">
                            </div>
                            <button type="button" @click="removeOfficer(index)" class="text-red-400 hover:text-red-300 p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </template>
                    <div x-show="selectedOfficers.length === 0" class="text-sm text-gray-500 p-4 border border-dashed border-[var(--color-surface-500)] rounded-xl text-center">
                        Belum ada petugas terpilih
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" @click="step = 1" class="px-6 py-3 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white rounded-xl font-medium transition-colors">
                    &larr; Kembali
                </button>
                <button type="button" @click="if(selectedOfficers.length > 0) step = 3" :disabled="selectedOfficers.length === 0" class="px-6 py-3 bg-[var(--color-accent)] hover:bg-blue-600 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Selanjutnya &rarr;
                </button>
            </div>
        </div>

        {{-- STEP 3: Konfirmasi --}}
        <div x-show="step === 3" style="display: none;" x-transition.opacity.duration.300ms class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6 space-y-5">
            <h3 class="text-xl font-bold text-white mb-4">Konfirmasi Penugasan</h3>
            
            <div class="bg-[var(--color-surface-700)] p-5 rounded-xl border border-[var(--color-surface-500)] space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-400">Periode Penugasan:</span>
                    <span class="text-white font-medium" x-text="start_date + (end_date ? ' s/d ' + end_date : ' (Selamanya)')"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Total Petugas:</span>
                    <span class="text-white font-medium" x-text="selectedOfficers.length + ' Orang'"></span>
                </div>
                <div class="border-t border-[var(--color-surface-500)] my-2"></div>
                <div class="flex justify-between text-lg">
                    <span class="text-gray-300">Total Penugasan Dibuat:</span>
                    <span class="text-[var(--color-accent)] font-bold" x-text="selectedOfficers.length"></span>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button type="button" @click="step = 2" class="px-6 py-3 bg-[var(--color-surface-600)] hover:bg-[var(--color-surface-500)] text-white rounded-xl font-medium transition-colors">
                    &larr; Kembali
                </button>
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl font-medium transition-colors">
                    Simpan Penugasan
                </button>
            </div>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assignmentWizard', () => ({
        step: 1,
        
        // Data sources
        zones: [],
        locations: [],
        searchResults: [],
        
        // Form states
        operation_id: '',
        operationStart: '',
        operationEnd: '',
        zone_id: '',
        location_id: '',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        searchQuery: '',
        selectedOfficers: [],
        
        // Loading states
        isLoadingZones: false,
        isLoadingLocations: false,
        isSearching: false,
        
        get canProceedToStep2() {
            return this.operation_id && this.zone_id && this.location_id && this.start_date;
        },

        onOperationChange(event) {
            // Pull start/end times from the selected <option>'s data attributes
            // so the admin sees the operation's window without picking a shift.
            const opt = event.target.selectedOptions[0];
            this.operationStart = opt?.dataset.start || '';
            this.operationEnd = opt?.dataset.end || '';
            this.fetchZones();
        },

        async fetchZones() {
            this.zone_id = '';
            this.location_id = '';
            this.zones = [];

            if (!this.operation_id) return;

            this.isLoadingZones = true;
            try {
                const res = await fetch(`/ajax/zones-by-operation?operation_id=${this.operation_id}`);
                this.zones = await res.json();
            } catch(e) { console.error(e); }
            this.isLoadingZones = false;
        },

        async fetchLocations() {
            this.location_id = '';
            this.locations = [];

            if (!this.zone_id) return;

            this.isLoadingLocations = true;
            try {
                const res = await fetch(`/ajax/locations-by-zone?zone_id=${this.zone_id}`);
                this.locations = await res.json();
            } catch(e) { console.error(e); }
            this.isLoadingLocations = false;
        },
        
        async searchOfficers() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            
            this.isSearching = true;
            try {
                const res = await fetch(`/ajax/officer-search?q=${this.searchQuery}`);
                const data = await res.json();
                
                // Filter out already selected officers
                const selectedIds = this.selectedOfficers.map(o => o.id);
                this.searchResults = data.filter(o => !selectedIds.includes(o.id));
            } catch(e) { console.error(e); }
            this.isSearching = false;
        },
        
        addOfficer(officer) {
            this.selectedOfficers.push(officer);
            this.searchQuery = '';
            this.searchResults = [];
        },
        
        removeOfficer(index) {
            this.selectedOfficers.splice(index, 1);
        }
    }));
});
</script>
@endpush
