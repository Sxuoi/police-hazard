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

    {{-- State: Waiting for Photo --}}
    <template x-if="state === 'waiting_for_photo'">
        <div class="space-y-4">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-white">Ambil Foto dari Kamera</h2>
                <p class="text-sm text-gray-400 mt-1">Gunakan kamera bawaan perangkat Anda</p>
            </div>

            <template x-if="errorMessage">
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-3 rounded-lg text-sm text-center mb-4" x-text="errorMessage"></div>
            </template>
            
            <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden" @change="handleCapture">
            
            <button
                @click="triggerCameraInput()"
                class="w-full py-6 rounded-xl bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] border-2 border-dashed border-[var(--color-accent)] text-white font-semibold transition-colors flex flex-col items-center justify-center gap-3"
            >
                <svg class="w-10 h-10 text-[var(--color-accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Buka Kamera
            </button>
        </div>
    </template>

    {{-- State: Compressing Photo --}}
    <template x-if="state === 'compressing_photo'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-blue-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Memproses Foto...</h2>
                <p class="text-sm text-gray-400 mt-1">Mohon tunggu sebentar</p>
            </div>
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


</div>
@endsection
