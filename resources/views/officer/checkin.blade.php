@extends('officer.layout')

@section('title', 'Check-In')

@section('content')
<div class="p-4" x-data="checkinScreen" x-init="init()">
    {{-- Back Button --}}
    <a href="/officer/assignments" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-white transition-colors mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    {{-- State: Idle --}}
    <template x-if="state === 'idle'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-[var(--color-accent)]/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Siap Check-In</h2>
                <p class="text-sm text-gray-400 mt-1">Pastikan Anda berada di lokasi penugasan</p>
            </div>
            <button
                @click="startCheckin()"
                class="w-full py-4 rounded-xl bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-semibold transition-colors"
            >
                Mulai Check-In
            </button>
        </div>
    </template>

    {{-- State: Acquiring GPS --}}
    <template x-if="state === 'acquiring_gps'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-blue-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Mengambil Lokasi GPS...</h2>
                <p class="text-sm text-gray-400 mt-1">Mohon tunggu, sedang menentukan posisi Anda</p>
            </div>
            <div class="w-full h-1 rounded-full bg-[var(--color-surface-600)] overflow-hidden">
                <div class="h-full bg-blue-500 rounded-full animate-pulse" style="width: 60%"></div>
            </div>
        </div>
    </template>

    {{-- State: Camera Open --}}
    <template x-if="state === 'camera_open'">
        <div class="space-y-4">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-white">Ambil Foto</h2>
                <p class="text-sm text-gray-400 mt-1">Arahkan kamera ke wajah Anda</p>
            </div>
            <div class="relative rounded-xl overflow-hidden bg-black aspect-[3/4]">
                <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <button
                    @click="toggleCamera()"
                    type="button"
                    class="absolute top-4 right-4 z-10 p-2.5 rounded-full bg-black/60 hover:bg-black/80 text-white border border-white/10 transition-colors cursor-pointer"
                    title="Ganti Kamera"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 6H16m0 0v5h5" />
                    </svg>
                </button>
            </div>
            <button
                @click="capturePhoto()"
                class="w-full py-4 rounded-xl bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-semibold transition-colors"
            >
                Ambil Foto
            </button>
        </div>
    </template>

    {{-- State: Photo Preview --}}
    <template x-if="state === 'photo_preview'">
        <div class="space-y-4">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-white">Preview Foto</h2>
                <p class="text-sm text-gray-400 mt-1">Pastikan foto terlihat jelas</p>
            </div>
            <div class="relative rounded-xl overflow-hidden bg-black aspect-[3/4]">
                <img :src="photoDataUrl" class="w-full h-full object-cover" alt="Preview foto check-in">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button
                    @click="retakePhoto()"
                    class="py-3 rounded-xl bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-gray-300 font-medium transition-colors hover:bg-[var(--color-surface-600)]"
                >
                    Ulangi
                </button>
                <button
                    @click="submitCheckin()"
                    class="py-3 rounded-xl bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-medium transition-colors"
                >
                    Kirim
                </button>
            </div>
        </div>
    </template>

    {{-- State: Submitting --}}
    <template x-if="state === 'submitting'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-[var(--color-accent)]/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-[var(--color-accent)] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Mengirim Check-In...</h2>
                <p class="text-sm text-gray-400 mt-1">Mohon tunggu</p>
            </div>
        </div>
    </template>

    {{-- State: Success --}}
    <template x-if="state === 'success'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-green-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Check-In Berhasil!</h2>
                <p class="text-sm text-gray-400 mt-1">Kehadiran Anda telah tercatat</p>
            </div>
            <a href="/officer/assignments" class="block w-full py-4 rounded-xl bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white font-medium text-center transition-colors hover:bg-[var(--color-surface-600)]">
                Kembali ke Penugasan
            </a>
        </div>
    </template>

    {{-- State: Error --}}
    <template x-if="state === 'error'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-red-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Check-In Gagal</h2>
                <p class="text-sm text-red-400 mt-1" x-text="errorMessage"></p>
            </div>
            <div class="space-y-3">
                <template x-if="bypassEligible">
                    <a :href="'/officer/bypass/' + (bypassId || '')" class="block w-full py-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 font-medium text-center transition-colors hover:bg-yellow-500/20">
                        Ajukan Bypass
                    </a>
                </template>
                <button
                    @click="state = 'idle'"
                    class="w-full py-4 rounded-xl bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white font-medium transition-colors hover:bg-[var(--color-surface-600)]"
                >
                    Coba Lagi
                </button>
            </div>
        </div>
    </template>

    {{-- Hidden canvas for photo capture --}}
    <canvas x-ref="canvas" class="hidden"></canvas>
</div>
@endsection
