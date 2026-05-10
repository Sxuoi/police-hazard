@extends('layouts.admin')
@section('title', 'Buat Zona') @section('page-title', 'Buat Zona Baru')
@section('content')
<div class="max-w-lg">
    <div class="bg-[var(--color-surface-800)] rounded-2xl border border-[var(--color-surface-600)] p-6">
        <form method="POST" action="{{ route('zones.store') }}">
            @csrf
            @if($errors->any())<div class="mb-6"><x-alert type="error">{{ $errors->first() }}</x-alert></div>@endif

            <div class="mb-5">
                <label for="operation_id" class="block text-sm font-medium text-gray-300 mb-2">Operasi <span class="text-red-400">*</span></label>
                <select id="operation_id" name="operation_id" required
                        class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]">
                    <option value="">Pilih operasi...</option>
                    @foreach($operations as $op)
                        <option value="{{ $op->id }}" @selected(old('operation_id') === $op->id)>
                            {{ $op->name }} ({{ $op->operation_type }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Nama Zona <span class="text-red-400">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="150"
                       class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]" />
            </div>

            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Deskripsi</label>
                <textarea id="description" name="description" rows="3" maxlength="500"
                          class="w-full px-4 py-3 bg-[var(--color-surface-700)] border border-[var(--color-surface-500)] rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] resize-none">{{ old('description') }}</textarea>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('zones.index') }}" class="flex-1 px-4 py-3 bg-[var(--color-surface-700)] hover:bg-[var(--color-surface-600)] text-gray-300 text-sm font-medium rounded-xl text-center transition-colors">Batal</a>
                <button type="submit" class="flex-1 px-4 py-3 bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white text-sm font-medium rounded-xl transition-colors cursor-pointer">Buat Zona</button>
            </div>
        </form>
    </div>
</div>
@endsection
