@extends('officer.layout')

@section('title', 'Bypass Request')

@section('content')
<div class="p-4" x-data="bypassScreen" x-init="init()">
    {{-- Back Button --}}
    <a href="/officer/assignments" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-white transition-colors mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>

    {{-- State: Form (new bypass request) --}}
    <template x-if="state === 'form'">
        <div class="space-y-4">
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto rounded-full bg-yellow-500/10 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <h2 class="text-lg font-semibold text-white">Ajukan Bypass</h2>
                <p class="text-sm text-gray-400 mt-1">Jelaskan alasan Anda tidak dapat check-in di lokasi</p>
            </div>

            {{-- No bundle: officer reached this page without a failed check-in --}}
            <template x-if="bundleMissing">
                <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-4 text-center space-y-3">
                    <p class="text-sm text-red-400">Data check-in tidak ditemukan.</p>
                    <p class="text-xs text-gray-400">Silakan kembali dan coba check-in terlebih dahulu. Bypass hanya tersedia setelah check-in ditolak.</p>
                    <a href="/officer/assignments" class="inline-block mt-2 px-4 py-2 rounded-lg bg-[var(--color-surface-700)] text-white text-sm hover:bg-[var(--color-surface-600)] transition-colors">
                        Kembali ke Penugasan
                    </a>
                </div>
            </template>

            {{-- Error Message --}}
            <template x-if="error">
                <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-3">
                    <p class="text-sm text-red-400" x-text="error"></p>
                </div>
            </template>

            <template x-if="!bundleMissing && bundle">
                <form @submit.prevent="submitBypass()" class="space-y-4">
                    {{-- Photo preview from the failed check-in --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Foto Check-In</label>
                        <div class="rounded-xl overflow-hidden bg-black aspect-[3/4] border border-[var(--color-surface-600)]">
                            <img :src="photoPreview" class="w-full h-full object-cover" alt="Foto check-in">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Foto ini akan dikirim bersama permintaan bypass.</p>
                    </div>

                    {{-- GPS info from the failed check-in --}}
                    <div class="rounded-lg bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-3 space-y-1">
                        <p class="text-xs text-gray-500">Lokasi tercatat</p>
                        <p class="text-sm text-gray-300" x-text="bundle.latitude.toFixed(6) + ', ' + bundle.longitude.toFixed(6)"></p>
                        <p class="text-xs text-gray-500" x-text="'Akurasi: ±' + Math.round(bundle.gps_accuracy) + ' m'"></p>
                        <p class="text-xs text-gray-500" x-text="'Alasan: ' + bundle.reason_code"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Catatan</label>
                        <textarea
                            x-model="officerNote"
                            rows="4"
                            placeholder="Jelaskan alasan bypass (min. 20 karakter)..."
                            required
                            minlength="20"
                            class="w-full px-4 py-3 rounded-lg bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent resize-none"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1" x-text="officerNote.length + '/20 karakter minimum'"></p>
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting || officerNote.length < 20"
                        class="w-full py-4 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-black font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting">Kirim Permintaan Bypass</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Mengirim...
                        </span>
                    </button>
                </form>
            </template>
        </div>
    </template>

    {{-- State: Pending (polling) --}}
    <template x-if="state === 'pending'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-yellow-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-yellow-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Menunggu Persetujuan</h2>
                <p class="text-sm text-gray-400 mt-1">Permintaan bypass Anda sedang ditinjau oleh supervisor</p>
            </div>
            <div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-4">
                <p class="text-xs text-gray-500">Status diperbarui otomatis</p>
            </div>
        </div>
    </template>

    {{-- State: Approved --}}
    <template x-if="state === 'approved'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-green-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Bypass Disetujui</h2>
                <p class="text-sm text-gray-400 mt-1">Kehadiran Anda telah dicatat melalui bypass</p>
            </div>
            <a href="/officer/assignments" class="block w-full py-4 rounded-xl bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white font-medium text-center transition-colors hover:bg-[var(--color-surface-600)]">
                Kembali ke Penugasan
            </a>
        </div>
    </template>

    {{-- State: Denied --}}
    <template x-if="state === 'denied'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-red-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Bypass Ditolak</h2>
                <p class="text-sm text-gray-400 mt-1">Permintaan bypass Anda tidak disetujui</p>
                <template x-if="reviewerNote">
                    <p class="text-sm text-gray-300 mt-2 italic" x-text="'Catatan: ' + reviewerNote"></p>
                </template>
            </div>
            <a href="/officer/assignments" class="block w-full py-4 rounded-xl bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white font-medium text-center transition-colors hover:bg-[var(--color-surface-600)]">
                Kembali ke Penugasan
            </a>
        </div>
    </template>

    {{-- State: Expired --}}
    <template x-if="state === 'expired'">
        <div class="text-center py-12 space-y-6">
            <div class="w-20 h-20 mx-auto rounded-full bg-gray-500/10 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-white">Bypass Kedaluwarsa</h2>
                <p class="text-sm text-gray-400 mt-1">Permintaan bypass telah melewati batas waktu</p>
            </div>
            <a href="/officer/assignments" class="block w-full py-4 rounded-xl bg-[var(--color-surface-700)] border border-[var(--color-surface-600)] text-white font-medium text-center transition-colors hover:bg-[var(--color-surface-600)]">
                Kembali ke Penugasan
            </a>
        </div>
    </template>
</div>
@endsection
