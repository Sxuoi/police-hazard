@extends('layouts.admin')
@section('title', 'Edit Petugas') @section('page-title', 'Edit Petugas: ' . $officer->name)

@section('content')
<div class="max-w-2xl">
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form method="POST" action="{{ route('officers.update', $officer->id) }}">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="mb-6">
                    <x-alert type="error">{{ $errors->first() }}</x-alert>
                </div>
            @endif

            @if(auth()->user()->isGodAdmin())
            <div class="mb-5">
                <label for="saker_id" class="block text-sm font-medium text-gray-300 mb-2">Satuan Kerja (Saker) <span class="text-red-400">*</span></label>
                <select id="saker_id" name="saker_id" required
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
                    <option value="">Pilih Satuan Kerja</option>
                    @foreach($sakers as $saker)
                        <option value="{{ $saker->id }}" @selected(old('saker_id', $officer->saker_id) === $saker->id)>{{ $saker->name }} ({{ $saker->code }})</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-300 mb-2">NRP</label>
                <input type="text" value="{{ $officer->nrp }}" disabled
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-gray-400 opacity-70 cursor-not-allowed" />
                <p class="text-xs text-gray-500 mt-1">NRP tidak dapat diubah setelah petugas didaftarkan.</p>
            </div>

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Lengkap <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $officer->name) }}" required maxlength="150"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">No. Telepon / WA</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $officer->phone) }}" maxlength="20"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $officer->email) }}" maxlength="150"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
            </div>

            <div class="mb-5">
                <label for="safung" class="block text-sm font-medium text-gray-300 mb-2">Satuan Fungsi (Safung)</label>
                <input type="text" id="safung" name="safung" value="{{ old('safung', $officer->safung) }}" maxlength="100"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            @if(auth()->user()->isGodAdmin())
            <div class="bg-[var(--color-surface-700)] p-5 rounded-xl border border-[var(--color-surface-500)] mb-6">
                <h3 class="text-sm font-semibold text-white mb-1">Ubah Password Petugas (Super Admin)</h3>
                <p class="text-xs text-gray-400 mb-4">Password saat ini tersimpan secara terenkripsi (one-way hash) sehingga tidak dapat dilihat. Isi kolom di bawah ini hanya jika Anda ingin mengubah atau mereset password petugas.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-300 mb-1">Password Baru</label>
                        <input type="password" id="password" name="password" minlength="8"
                               placeholder="Minimal 8 karakter"
                               class="w-full px-4 py-2.5 bg-[var(--color-surface-800)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-gray-300 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                               placeholder="Ulangi password baru"
                               class="w-full px-4 py-2.5 bg-[var(--color-surface-800)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                    </div>
                </div>
            </div>
            @endif

            <div class="mb-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $officer->is_active))
                           class="w-5 h-5 rounded border-[var(--color-surface-500)] text-[var(--color-accent)] focus:ring-[var(--color-accent)] bg-[var(--color-surface-700)]" />
                    <div>
                        <div class="text-sm font-medium text-white">Status Aktif</div>
                        <div class="text-xs text-gray-400">Petugas yang tidak aktif tidak akan muncul dalam pencarian penugasan.</div>
                    </div>
                </label>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('officers.show', $officer->id) }}"
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
