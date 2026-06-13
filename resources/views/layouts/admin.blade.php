<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Police Hazard — Admin Panel">
    <title>@yield('title', 'Dashboard') — Police Hazard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body
    class="min-h-screen bg-[var(--color-surface-900)] text-gray-200 antialiased"
    x-data="{ sidebarOpen: $store.sidebar.open }"
    @resize.window="sidebarOpen = window.innerWidth >= 1024"
>
    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen && window.innerWidth < 1024"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        style="display: none;"
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed top-0 left-0 z-40 h-screen w-64 bg-[var(--color-surface-800)] border-r border-[var(--color-surface-600)] transition-transform duration-200 flex flex-col"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
        {{-- Brand --}}
        <div class="h-16 flex items-center gap-3 px-5 border-b border-[var(--color-surface-600)]">
            <div class="w-8 h-8 rounded-lg bg-[var(--color-accent)]/10 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[var(--color-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <span class="font-semibold text-white text-sm tracking-tight">Police Hazard</span>
        </div>

        {{-- Navigation --}}
        @php
            $isPoliceHazardActive = request()->routeIs(['dashboard', 'operations.*', 'zones.*', 'locations.*', 'officers.*', 'assignments.*', 'reports.*']);
            $isLayanan110Active = request()->routeIs(['units.*', 'operator-110.*']);
            $defaultMenu = $isLayanan110Active ? "'layanan_110'" : ($isPoliceHazardActive ? "'police_hazard'" : "''");
        @endphp
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" x-data="{ openMenu: {{ $defaultMenu }} }">
            <p class="px-3 text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Menu Utama</p>

            {{-- Dropdown Police Hazard --}}
            <div class="mb-2">
                <button @click="openMenu = openMenu === 'police_hazard' ? '' : 'police_hazard'"
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ $isPoliceHazardActive ? 'bg-[var(--color-surface-600)] text-white' : 'text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)]' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 {{ $isPoliceHazardActive ? 'text-[#3b82f6]' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Police Hazard</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="openMenu === 'police_hazard' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'police_hazard'" 
                     x-transition:enter="transition-all ease-in-out duration-200"
                     x-transition:enter-start="opacity-0 max-h-0"
                     x-transition:enter-end="opacity-100 max-h-screen"
                     x-transition:leave="transition-all ease-in-out duration-200"
                     x-transition:leave-start="opacity-100 max-h-screen"
                     x-transition:leave-end="opacity-0 max-h-0"
                     class="overflow-hidden pl-5 pr-2 py-1 space-y-1 mt-1 border-l-2 border-[var(--color-surface-600)] ml-5">
                    
                    <x-sidebar-item href="{{ route('dashboard') }}" icon="home" :active="request()->routeIs('dashboard')">Beranda</x-sidebar-item>
                    <x-sidebar-item href="{{ route('operations.index') }}" icon="briefcase" :active="request()->routeIs('operations.*')">Operasi</x-sidebar-item>
                    <x-sidebar-item href="{{ route('zones.index') }}" icon="layers" :active="request()->routeIs('zones.*')">Zona</x-sidebar-item>
                    <x-sidebar-item href="{{ route('locations.index') }}" icon="map-pin" :active="request()->routeIs('locations.*')">Lokasi</x-sidebar-item>
                    <x-sidebar-item href="{{ route('officers.index') }}" icon="users" :active="request()->routeIs('officers.*')">Anggota</x-sidebar-item>
                    <x-sidebar-item href="{{ route('assignments.index') }}" icon="clipboard" :active="request()->routeIs('assignments.*')">Penugasan</x-sidebar-item>
                    <x-sidebar-item href="{{ route('reports.index') }}" icon="bar-chart" :active="request()->routeIs('reports.*')">Rekapitulasi</x-sidebar-item>
                </div>
            </div>

            {{-- Dropdown Layanan 110 --}}
            <div class="mb-2">
                <button @click="openMenu = openMenu === 'layanan_110' ? '' : 'layanan_110'"
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ $isLayanan110Active ? 'bg-[var(--color-surface-600)] text-white' : 'text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)]' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 {{ $isLayanan110Active ? 'text-[#3b82f6]' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Layanan 110</span>
                    </div>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="openMenu === 'layanan_110' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="openMenu === 'layanan_110'" 
                     x-transition:enter="transition-all ease-in-out duration-200"
                     x-transition:enter-start="opacity-0 max-h-0"
                     x-transition:enter-end="opacity-100 max-h-screen"
                     x-transition:leave="transition-all ease-in-out duration-200"
                     x-transition:leave-start="opacity-100 max-h-screen"
                     x-transition:leave-end="opacity-0 max-h-0"
                     class="overflow-hidden pl-5 pr-2 py-1 space-y-1 mt-1 border-l-2 border-[var(--color-surface-600)] ml-5">
                    
                    <x-sidebar-item href="{{ route('operator-110.index') }}" icon="clipboard-list" :active="request()->routeIs('operator-110.index') || request()->routeIs('operator-110.show')">Daftar Laporan</x-sidebar-item>
                    <x-sidebar-item href="{{ route('operator-110.monitor') }}" icon="globe" :active="request()->routeIs('operator-110.monitor')">Peta Pantauan</x-sidebar-item>
                    <x-sidebar-item href="{{ route('units.index') }}" icon="truck" :active="request()->routeIs('units.*')">Manajemen Armada</x-sidebar-item>
                </div>
            </div>

            <p class="px-3 text-xs font-medium text-gray-500 uppercase tracking-wider mt-6 mb-2">Sistem</p>

            <x-sidebar-item href="#" icon="shield" :active="request()->routeIs('bypass.*')">
                Bypass
            </x-sidebar-item>

            <x-sidebar-item href="{{ route('audit-logs.index') }}" icon="scroll" :active="request()->routeIs('audit-logs.*')">
                Audit Log
            </x-sidebar-item>

            @if(auth()->check() && auth()->user()->isGodAdmin())
                <p class="px-3 text-xs font-medium text-gray-500 uppercase tracking-wider mt-6 mb-2">God Admin</p>

                <x-sidebar-item href="#" icon="flame" :active="request()->routeIs('heatmap')">
                    Peta Panas
                </x-sidebar-item>

                <x-sidebar-item href="#" icon="building" :active="request()->routeIs('sakers.*')">
                    Kelola Saker
                </x-sidebar-item>
            @endif
        </nav>

        {{-- User Footer --}}
        <div class="border-t border-[var(--color-surface-600)] p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[var(--color-accent)]/20 flex items-center justify-center text-sm font-medium text-[var(--color-accent)]">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400">{{ auth()->user()->nrp }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="lg:pl-64 min-h-screen flex flex-col">
        {{-- Top Bar --}}
        <header class="sticky top-0 z-20 h-16 bg-[var(--color-surface-800)]/80 backdrop-blur-xl border-b border-[var(--color-surface-600)] flex items-center justify-between px-4 lg:px-6">
            {{-- Mobile hamburger --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            {{-- Page Title --}}
            <h2 class="text-lg font-semibold text-white hidden lg:block">@yield('page-title', 'Dashboard')</h2>

            <div class="flex items-center gap-3">
                {{-- Saker Badge --}}
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[var(--color-accent)]/10 text-xs font-medium text-[var(--color-accent)]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    {{ auth()->user()->saker?->code ?? 'ALL' }}
                </span>

                {{-- Notification Bell --}}
                <button class="relative p-2 rounded-lg text-gray-400 hover:text-white hover:bg-[var(--color-surface-600)] transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    {{-- TODO: notification count badge --}}
                </button>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mx-4 lg:mx-6 mt-4">
                <x-alert type="success">{{ session('success') }}</x-alert>
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 lg:mx-6 mt-4">
                <x-alert type="error">{{ session('error') }}</x-alert>
            </div>
        @endif

        {{-- Page Content --}}
        <main class="flex-1 p-4 lg:p-6">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
