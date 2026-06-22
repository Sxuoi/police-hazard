<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\JenisGangguanRepositoryInterface;
use Illuminate\Http\Request;

class JenisGangguanController extends Controller
{
    public function __construct(
        protected JenisGangguanRepositoryInterface $jenisGangguanRepository
    ) {}

    public function index()
    {
        $jenisGangguans = $this->jenisGangguanRepository->paginate(15);
        return view('jenis_gangguan.index', compact('jenisGangguans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
        ]);

        $validated['saker_id'] = auth()->user()->saker_id ?? auth()->id();

        $this->jenisGangguanRepository->create($validated);

        return redirect()->route('jenis-gangguan.index')->with('success', 'Jenis Gangguan berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $jenisGangguan = $this->jenisGangguanRepository->findById($id);
        if (!$jenisGangguan) abort(404);

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
        ]);

        $this->jenisGangguanRepository->update($jenisGangguan, $validated);

        return redirect()->route('jenis-gangguan.index')->with('success', 'Jenis Gangguan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $jenisGangguan = $this->jenisGangguanRepository->findById($id);
        if (!$jenisGangguan) abort(404);

        $this->jenisGangguanRepository->delete($jenisGangguan);

        return redirect()->route('jenis-gangguan.index')->with('success', 'Jenis Gangguan berhasil dihapus.');
    }
}
