@extends('layouts.admin')

@section('title', 'Tambah Admin')
@section('page-title', 'Tambah Saker / Admin')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-xl font-bold text-white tracking-tight">Tambah Admin Baru</h3>
        <a href="{{ route('sakers.index') }}" class="text-sm font-medium text-gray-400 hover:text-white transition-colors">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] rounded-2xl p-6 shadow-xl">
        <form action="{{ route('sakers.store') }}" method="POST" class="space-y-6" x-data="{
            type: '{{ old('type', '') }}',
            showPassword: false,
            showPasswordConfirmation: false
        }">
            @csrf

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Satuan Kerja <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       placeholder="Misal: POLRESTABES SEMARANG"
                       class="w-full px-4 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Code --}}
            <div>
                <label for="code" class="block text-sm font-medium text-gray-300 mb-2">Kode Saker <span class="text-red-400">*</span></label>
                <input type="text" name="code" id="code" value="{{ old('code') }}" required
                       placeholder="Misal: PRTBS-SMG"
                       class="w-full px-4 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                @error('code') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Login <span class="text-red-400">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       placeholder="admin@polres.id"
                       class="w-full px-4 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                @error('email') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password <span class="text-red-400">*</span></label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                           class="w-full pl-4 pr-12 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-white transition-colors">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter.</p>
                @error('password') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Password Confirmation --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password <span class="text-red-400">*</span></label>
                <div class="relative">
                    <input :type="showPasswordConfirmation ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                           class="w-full pl-4 pr-12 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                    <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-white transition-colors">
                        <svg x-show="!showPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </button>
                </div>
                @error('password_confirmation') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            @if($admin->type === 'MABES')
                {{-- Type (Hanya terlihat untuk MABES) --}}
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-300 mb-2">Tipe Saker <span class="text-red-400">*</span></label>
                    <select name="type" id="type" required x-model="type"
                            class="w-full px-4 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                        <option value="">-- Pilih Tipe --</option>
                        <option value="POLDA">POLDA</option>
                        <option value="POLRESTABES">POLRESTABES</option>
                        <option value="POLSEK">POLSEK</option>
                    </select>
                    @error('type') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Parent ID (Hanya terlihat untuk MABES ketika memilih POLRESTABES/POLSEK) --}}
                <div x-show="type === 'POLRESTABES' || type === 'POLSEK'" style="display: none;"
                     x-data="{ 
                        allSakers: {{ $allSakers->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'type' => $s->type])->toJson() }}
                     }">
                    <label for="parent_id" class="block text-sm font-medium text-gray-300 mb-2">Pilih Induk (Parent) <span class="text-red-400">*</span></label>
                    <select name="parent_id" id="parent_id"
                            class="w-full px-4 py-2.5 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent">
                        <option value="">-- Pilih Saker Induk --</option>
                        <template x-for="saker in allSakers.filter(s => (type === 'POLRESTABES' && s.type === 'POLDA') || (type === 'POLSEK' && s.type === 'POLRESTABES'))" :key="saker.id">
                            <option :value="saker.id" x-text="`${saker.name} (${saker.type})`"></option>
                        </template>
                    </select>
                    @error('parent_id') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="p-4 bg-[var(--color-surface-700)] rounded-xl border border-[var(--color-surface-600)]">
                    <p class="text-sm text-gray-300">
                        @if($admin->type === 'POLDA')
                            Sebagai Admin POLDA, Anda akan menambahkan admin tingkat <strong>POLRESTABES</strong> yang secara otomatis berada di bawah komando Anda.
                        @elseif($admin->type === 'POLRESTABES')
                            Sebagai Admin POLRESTABES, Anda akan menambahkan admin tingkat <strong>POLSEK</strong> yang secara otomatis berada di bawah komando Anda.
                        @endif
                    </p>
                </div>
            @endif

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-medium rounded-xl transition-all shadow-lg shadow-[var(--color-accent)]/20">
                    Simpan Admin Baru
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
