@extends('layouts.admin')

@section('title', 'Buat Operasi')
@section('page-title', 'Buat Operasi Baru')

@section('content')
<div class="max-w-2xl">
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form method="POST" action="{{ route('operations.store') }}" x-data="{ type: '{{ old('operation_type', 'PH') }}' }">
            @csrf

            @if($errors->any())
                <div class="mb-6">
                    <x-alert type="error">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            {{-- Saker (God Admin only) --}}
            @if(auth()->user()->isGodAdmin())
                <div class="mb-5">
                    <label for="saker_id" class="block text-sm font-medium text-gray-300 mb-2">Satuan Kerja</label>
                    <select id="saker_id" name="saker_id" required
                            class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
                        @foreach($sakers as $saker)
                            <option value="{{ $saker->id }}" @selected(old('saker_id') === $saker->id)>
                                {{ $saker->code }} — {{ $saker->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Name --}}
            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Operasi <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="150"
                       placeholder="Contoh: Operasi Keamanan Natal 2026"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            {{-- Description --}}
            <div class="mb-5">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                <textarea id="description" name="description" rows="3" maxlength="500"
                          placeholder="Keterangan singkat tentang operasi..."
                          class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] resize-none">{{ old('description') }}</textarea>
            </div>

            {{-- Operation Type --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-3">Tipe Operasi <span class="text-red-400">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="operation_type" value="PH" x-model="type" class="sr-only peer" />
                        <div class="p-4 rounded-xl border-2 border-[var(--color-surface-500)] peer-checked:border-[var(--color-accent)] peer-checked:bg-[var(--color-accent)]/5 transition-all">
                            <div class="font-medium text-white text-sm">PH (Pos Hadang)</div>
                            <div class="text-xs text-gray-400 mt-1">Titik patroli statis, satu officer per shift</div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="operation_type" value="PATROL" x-model="type" class="sr-only peer" />
                        <div class="p-4 rounded-xl border-2 border-[var(--color-surface-500)] peer-checked:border-[var(--color-accent)] peer-checked:bg-[var(--color-accent)]/5 transition-all">
                            <div class="font-medium text-white text-sm">Patrol (Patroli)</div>
                            <div class="text-xs text-gray-400 mt-1">Multi-officer, lebih fleksibel</div>
                        </div>
                    </label>
                </div>
                <p class="text-xs text-yellow-400/80 mt-2">⚠️ Tipe tidak dapat diubah setelah zona pertama dibuat.</p>
            </div>

            {{-- Date Range --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-300 mb-2">Tanggal Mulai <span class="text-red-400">*</span></label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-300 mb-2">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada batas akhir.</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <a href="{{ route('operations.index') }}"
                   class="flex-1 px-4 py-3 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm font-medium rounded-xl text-center transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors cursor-pointer">
                    Buat Operasi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
