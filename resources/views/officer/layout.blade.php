<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Police Hazard — Officer Mobile">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="theme-color" content="#0f1117">
    <title>@yield('title', 'Officer') — Police Hazard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body
    class="min-h-screen bg-[var(--color-surface-900)] text-gray-200 antialiased"
    x-data="officerApp"
>
    {{-- Navigation Bar --}}
    <nav class="sticky top-0 z-30 h-14 bg-[var(--color-surface-800)]/90 backdrop-blur-xl border-b border-[var(--color-surface-600)] flex items-center justify-between px-4">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-[var(--color-accent)]/10 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[var(--color-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <span class="font-semibold text-white text-sm">Police Hazard</span>
        </div>

        <template x-if="officer">
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400" x-text="officer.nrp"></span>
                <button @click="logout()" class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </div>
        </template>
    </nav>

    {{-- Bottom Navigation (shown when logged in) --}}
    <template x-if="officer">
        <nav class="fixed bottom-0 left-0 right-0 z-30 h-14 bg-[var(--color-surface-800)] border-t border-[var(--color-surface-600)] flex items-center justify-around px-2">
            <a href="/officer/assignments" class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-[var(--color-accent)] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="text-[10px]">Tugas</span>
            </a>
            <a href="/officer/history" class="flex flex-col items-center gap-0.5 text-gray-400 hover:text-[var(--color-accent)] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-[10px]">Riwayat</span>
            </a>
        </nav>
    </template>

    {{-- Page Content --}}
    <main class="pb-16">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
