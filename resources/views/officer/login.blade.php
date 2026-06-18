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
            <h1 class="text-xl font-bold text-white">Mini Command Center</h1>
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

            <div x-data="{ show: false }">
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                <div class="relative">
                    <input
                        id="password"
                        :type="show ? 'text' : 'password'"
                        x-model="password"
                        placeholder="Masukkan password"
                        required
                        class="w-full pl-4 pr-12 py-3 rounded-lg bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-white transition-colors focus:outline-none"
                    >
                        <svg x-show="!show" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="show" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
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
