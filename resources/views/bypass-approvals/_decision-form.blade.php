{{-- Decision form: approve/deny with reviewer_note textarea (min 20 chars) --}}
<div class="rounded-xl bg-[var(--color-surface-800)] border border-[var(--color-surface-600)] p-6 space-y-4">
    <h3 class="text-lg font-semibold text-white">Keputusan</h3>

    @if($errors->any())
        <div class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div x-data="{ action: '' }">
        <div class="space-y-3">
            <label class="block text-sm text-gray-300">Catatan Reviewer <span class="text-gray-500">(min. 20 karakter)</span></label>
            <textarea
                form="decision-form"
                name="reviewer_note"
                rows="4"
                minlength="20"
                required
                placeholder="Tuliskan alasan keputusan Anda (minimal 20 karakter)..."
                class="w-full rounded-lg bg-[var(--color-surface-700)] border-[var(--color-surface-600)] text-gray-200 text-sm px-4 py-3 placeholder-gray-500 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
            >{{ old('reviewer_note') }}</textarea>
        </div>

        <div class="flex gap-3 mt-4">
            {{-- Approve Form --}}
            <form id="approve-form" method="POST" action="{{ route('bypass-approvals.approve', $bypass->id) }}">
                @csrf
                <input type="hidden" name="reviewer_note" x-ref="approveNote">
                <button
                    type="submit"
                    @click="$refs.approveNote.value = $el.closest('.space-y-4').querySelector('textarea').value"
                    class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition-colors cursor-pointer"
                >
                    Setujui
                </button>
            </form>

            {{-- Deny Form --}}
            <form id="deny-form" method="POST" action="{{ route('bypass-approvals.deny', $bypass->id) }}">
                @csrf
                <input type="hidden" name="reviewer_note" x-ref="denyNote">
                <button
                    type="submit"
                    @click="$refs.denyNote.value = $el.closest('.space-y-4').querySelector('textarea').value"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors cursor-pointer"
                >
                    Tolak
                </button>
            </form>
        </div>
    </div>
</div>
