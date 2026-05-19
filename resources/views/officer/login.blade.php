@extends('officer.layout')

@section('title', 'Login')

@section('content')
<div class="flex items-center justify-center min-h-[calc(100vh-3.5rem)] px-4" x-data="officerLogin">
    <div class="w-full max-w-sm space-y-6">
        {{-- Logo --}}
        <div class="text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-[var(--color-accent)]/10 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[var(--color-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-white">Police Hazard</h1>
            <p class="text-sm text-gray-400 mt-1">Login Anggota</p>
        </div>

        {{-- Error Message --}}
        <template x-if="error">
            <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-3">
                <p class="text-sm text-red-400" x-text="error"></p>
            </div>
        </template>

        {{-- Login Form --}}
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="nrp" class="block text-sm font-medium text-gray-300 mb-1">NRP</label>
                <input
                    id="nrp"
                    type="text"
                    x-model="nrp"
                    placeholder="Masukkan NRP"
                    required
                    class="w-full px-4 py-3 rounded-lg bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                <input
                    id="password"
                    type="password"
                    x-model="password"
                    placeholder="Masukkan password"
                    required
                    class="w-full px-4 py-3 rounded-lg bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent"
                >
            </div>

            <button
                type="submit"
                :disabled="loading"
                class="w-full py-3 rounded-lg bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!loading">Masuk</span>
                <span x-show="loading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Memproses...
                </span>
            </button>
        </form>
    </div>
</div>
@endsection
