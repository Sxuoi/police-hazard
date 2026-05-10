<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Police Hazard — Sistem Manajemen Kehadiran Polri">
    <title>Login — Police Hazard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-[var(--color-surface-900)]">

    <div class="w-full max-w-md mx-auto px-6" x-data="{ loading: false }">

        {{-- Logo / Branding --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--color-accent)]/10 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[var(--color-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-white tracking-tight">Police Hazard</h1>
            <p class="text-sm text-gray-400 mt-1">Sistem Manajemen Kehadiran</p>
        </div>

        {{-- Login Card --}}
        <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-8 shadow-xl">

            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-400 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" @submit="loading = true">
                @csrf

                {{-- NRP Field --}}
                <div class="mb-5">
                    <label for="nrp" class="block text-sm font-medium text-gray-300 mb-2">NRP</label>
                    <input
                        type="text"
                        id="nrp"
                        name="nrp"
                        value="{{ old('nrp') }}"
                        inputmode="numeric"
                        autocomplete="username"
                        required
                        autofocus
                        placeholder="Nomor Registrasi Pokok"
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-all duration-200"
                    />
                </div>

                {{-- Password Field --}}
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-all duration-200"
                    />
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-3 px-4 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-medium rounded-xl transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                    <template x-if="loading">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </template>
                    <span x-text="loading ? 'Memproses...' : 'Masuk'"></span>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-600 mt-8">
            &copy; {{ date('Y') }} Police Hazard v2.1 — Kepolisian Negara Republik Indonesia
        </p>
    </div>

</body>
</html>
