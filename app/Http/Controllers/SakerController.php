<?php

namespace App\Http\Controllers;

use App\Models\Saker;
use App\Http\Requests\StoreSakerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SakerController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('web')->user();
        
        $query = Saker::query();
        
        if ($admin->type !== 'MABES') {
            // Only show direct children
            $query->where('parent_id', $admin->id);
        }
        $sakers = $query->with('parent')->orderBy('type')->orderBy('name')->get();
        
        return view('sakers.index', compact('sakers', 'admin'));
    }

    public function create()
    {
        $admin = Auth::guard('web')->user();
        
        if ($admin->type === 'POLSEK') {
            abort(403, 'Unauthorized Action. Admin Polsek tidak berhak membuat akun komando di bawahnya.');
        }

        $allSakers = collect();
        if ($admin->type === 'MABES') {
            $allSakers = Saker::where('type', '!=', 'POLSEK')->orderBy('name')->get();
        }
        
        return view('sakers.create', compact('admin', 'allSakers'));
    }

    public function store(StoreSakerRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $admin = Auth::guard('web')->user();

        // Enforcement (Pemberlakuan Otomatis) Berjenjang
        if ($admin->type === 'POLRESTABES') {
            $validated['type'] = 'POLSEK';
            $validated['parent_id'] = $admin->id;
        } elseif ($admin->type === 'POLDA') {
            // Jika admin adalah Polda biasa (bukan konteks God Admin mutlak)
            $validated['type'] = 'POLRESTABES';
            $validated['parent_id'] = $admin->id;
        } elseif ($admin->type === 'POLSEK') {
            // Pengaman lapis kedua di Controller (selain FormRequest)
            abort(403, 'Unauthorized Action. Admin Polsek tidak berhak membuat akun komando di bawahnya.');
        } else {
            // Konteks God Admin / Absolut: 
            // Tetap menghargai input 'type' dan 'parent_id' dari request,
            // asalkan diisi oleh form UI milik God Admin.
        }

        // Enkripsi Password jika dikirim mentah dari Request
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        Saker::create($validated);

        return redirect()->route('sakers.index')->with('success', 'Akun Komando berhasil dibuat dengan penegakan hierarki!');
    }

    public function edit($id)
    {
        $admin = Auth::guard('web')->user();
        $saker = Saker::findOrFail($id);
        
        // Authorization check: Admin can only edit their direct children (or all if MABES)
        if ($admin->type !== 'MABES' && $saker->parent_id !== $admin->id) {
            abort(403, 'Unauthorized Action. Anda hanya dapat mengedit admin bawahan langsung Anda.');
        }

        $allSakers = collect();
        if ($admin->type === 'MABES') {
            $allSakers = Saker::where('type', '!=', 'POLSEK')->orderBy('name')->get();
        }

        return view('sakers.edit', compact('saker', 'admin', 'allSakers'));
    }

    public function update(\App\Http\Requests\UpdateSakerRequest $request, $id): RedirectResponse
    {
        $saker = Saker::findOrFail($id);
        $admin = Auth::guard('web')->user();

        // Authorization check
        if ($admin->type !== 'MABES' && $saker->parent_id !== $admin->id) {
            abort(403, 'Unauthorized Action. Anda hanya dapat mengedit admin bawahan langsung Anda.');
        }

        $validated = $request->validated();

        if ($admin->type === 'POLRESTABES') {
            $validated['type'] = 'POLSEK';
            $validated['parent_id'] = $admin->id;
        } elseif ($admin->type === 'POLDA') {
            $validated['type'] = 'POLRESTABES';
            $validated['parent_id'] = $admin->id;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $saker->update($validated);

        return redirect()->route('sakers.index')->with('success', 'Data Akun Komando berhasil diperbarui!');
    }

    public function destroy($id): RedirectResponse
    {
        $saker = Saker::findOrFail($id);
        $admin = Auth::guard('web')->user();

        // Authorization check
        if ($admin->type !== 'MABES' && $saker->parent_id !== $admin->id) {
            abort(403, 'Unauthorized Action. Anda hanya dapat menghapus admin bawahan langsung Anda.');
        }

        // Integrity check: prevent deletion if it has children or officers
        if ($saker->children()->exists()) {
            return redirect()->back()->with('error', 'Gagal menghapus! Satuan Kerja ini masih memiliki bawahan (anak). Hapus atau pindahkan bawahan terlebih dahulu.');
        }

        if ($saker->officers()->exists()) {
            return redirect()->back()->with('error', 'Gagal menghapus! Satuan Kerja ini masih memiliki anggota (officer). Hapus atau pindahkan anggota terlebih dahulu.');
        }

        $saker->delete();

        return redirect()->route('sakers.index')->with('success', 'Akun Komando berhasil dihapus!');
    }
}
