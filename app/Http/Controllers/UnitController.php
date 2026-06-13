<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        protected UnitRepositoryInterface $unitRepository
    ) {}

    public function index()
    {
        $units = $this->unitRepository->paginate(15);
        return view('units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_unit' => 'required|string|max:255',
            'no_wa' => 'required|string|max:50',
        ]);

        $this->unitRepository->create($validated);

        return redirect()->route('units.index')->with('success', 'Unit armada berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $unit = $this->unitRepository->findById($id);
        if (!$unit) abort(404);

        $validated = $request->validate([
            'nama_unit' => 'required|string|max:255',
            'no_wa' => 'required|string|max:50',
        ]);

        $this->unitRepository->update($unit, $validated);

        return redirect()->route('units.index')->with('success', 'Unit armada berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $unit = $this->unitRepository->findById($id);
        if (!$unit) abort(404);

        $this->unitRepository->delete($unit);

        return redirect()->route('units.index')->with('success', 'Unit armada berhasil dihapus.');
    }
}
