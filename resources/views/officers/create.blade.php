@extends('layouts.admin')
@section('title', 'Tambah Anggota') @section('page-title', 'Tambah Anggota Baru')

@section('content')
<div class="max-w-2xl">
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form method="POST" action="{{ route('officers.store') }}">
            @csrf

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
                        <option value="{{ $saker->id }}" @selected(old('saker_id', auth()->user()->saker_id) === $saker->id)>{{ $saker->name }} ({{ $saker->code }})</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Lengkap <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="150"
                       placeholder="Nama lengkap anggota"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="mb-5">
                <label for="nrp" class="block text-sm font-medium text-gray-300 mb-2">NRP <span class="text-red-400">*</span></label>
                <input type="text" id="nrp" name="nrp" value="{{ old('nrp') }}" required maxlength="20"
                       placeholder="Nomor Registrasi Pokok"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">No. Telepon / WA</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="20"
                           placeholder="08xxxxxxxxxx"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="150"
                           placeholder="email@example.com"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
            </div>

            <div class="mb-5">
                <label for="safung" class="block text-sm font-medium text-gray-300 mb-2">Satuan Fungsi (Safung)</label>
                <input type="text" id="safung" name="safung" value="{{ old('safung') }}" maxlength="100"
                       placeholder="Contoh: Sabhara, Reskrim, Lantas"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password <span class="text-red-400">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8"
                           placeholder="Minimal 8 karakter"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password <span class="text-red-400">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                           placeholder="Ulangi password"
                           class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('officers.index') }}"
                   class="flex-1 px-4 py-3 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm font-medium rounded-xl text-center transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors cursor-pointer">
                    Simpan Anggota
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
