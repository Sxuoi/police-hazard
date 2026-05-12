@extends('layouts.admin')

@section('title', 'Edit Operasi')
@section('page-title', 'Edit Operasi: ' . $operation->name)

@section('content')
<div class="max-w-2xl">
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form method="POST" action="{{ route('operations.update', $operation->id) }}" x-data="{ type: '{{ old('operation_type', $operation->operation_type) }}' }">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="mb-6">
                    <x-alert type="error">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            {{-- Name --}}
            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Operasi <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $operation->name) }}" required maxlength="150"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            {{-- Description --}}
            <div class="mb-5">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                <textarea id="description" name="description" rows="3" maxlength="500"
                          class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] resize-none">{{ old('description', $operation->description) }}</textarea>
            </div>

            {{-- Operation Type --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-3">Tipe Operasi <span class="text-red-400">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="operation_type" value="PH" x-model="type" class="sr-only peer" @if($operation->zones()->exists()) disabled @endif />
                        <div class="p-4 rounded-xl border-2 border-[var(--color-surface-500)] peer-checked:border-[var(--color-accent)] peer-checked:bg-[var(--color-accent)]/5 transition-all @if($operation->zones()->exists()) opacity-50 cursor-not-allowed @endif">
                            <div class="font-medium text-white text-sm">PH (Polisi Hazard)</div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="operation_type" value="PATROL" x-model="type" class="sr-only peer" @if($operation->zones()->exists()) disabled @endif />
                        <div class="p-4 rounded-xl border-2 border-[var(--color-surface-500)] peer-checked:border-[var(--color-accent)] peer-checked:bg-[var(--color-accent)]/5 transition-all @if($operation->zones()->exists()) opacity-50 cursor-not-allowed @endif">
                            <div class="font-medium text-white text-sm">Patrol (Patroli)</div>
                        </div>
                    </label>
                </div>
                @if($operation->zones()->exists())
                    <p class="text-xs text-yellow-400/80 mt-2">⚠️ Tipe tidak dapat diubah karena operasi ini sudah memiliki zona.</p>
                @endif
            </div>

            {{-- Time Range --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-300 mb-2">Waktu Mulai <span class="text-red-400">*</span></label>
                    <input type="time" id="start_time" name="start_time" value="{{ old('start_time', $operation->start_time) }}" required
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-300 mb-2">Waktu Selesai</label>
                    <input type="time" id="end_time" name="end_time" value="{{ old('end_time', $operation->end_time) }}"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
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
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
