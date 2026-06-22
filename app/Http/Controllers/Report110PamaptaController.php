<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReport110Watermark;
use App\Models\Report110;
use App\Repositories\Contracts\Report110RepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Report110PamaptaController extends Controller
{
    public function __construct(
        protected Report110RepositoryInterface $reportRepository,
    ) {}

    public function show(Request $request, string $token)
    {
        $report = $this->reportRepository->findByToken($token);
        
        if (!$report) {
            abort(404, 'Laporan tidak ditemukan.');
        }

        $isUnlocked = false;
        if ($report->status === 'Sudah penanganan') {
            if (Auth::check() && Auth::user()->hasRole('god.admin')) {
                $isUnlocked = true;
            } else if (session()->has("unlocked_110_{$report->id}")) {
                $isUnlocked = true;
            }
        } else {
            $isUnlocked = true; // Still open
        }

        return view('reports_110.pamapta_form', compact('report', 'isUnlocked'));
    }

    public function arrive(Request $request, string $token)
    {
        $report = $this->reportRepository->findByToken($token);
        if (!$report) return response()->json(['error' => 'Not found'], 404);

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'alamat' => 'nullable|string'
        ]);

        $this->reportRepository->updateSpatial($report, $request->lat, $request->lng, [
            'alamat_aktual_110' => $request->alamat,
            'waktu_mendatangi_tkp' => now(),
            'status' => 'Sedang penanganan'
        ]);

        return response()->json(['success' => true]);
    }

    public function updateLocation(Request $request, string $token)
    {
        $report = $this->reportRepository->findByToken($token);
        if (!$report) return response()->json(['error' => 'Not found'], 404);

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $this->reportRepository->updateSpatial($report, $request->lat, $request->lng);

        return response()->json(['success' => true]);
    }

    public function unlock(Request $request, string $token)
    {
        $report = $this->reportRepository->findByToken($token);
        if (!$report) return back()->with('error', 'Laporan tidak ditemukan.');

        if ($request->kode_tiketing === $report->no_tiketing) {
            session()->put("unlocked_110_{$report->id}", true);
            return back()->with('success', 'Form berhasil dibuka untuk edit.');
        }

        return back()->with('error', 'Kode Tiketing salah.');
    }

    public function complete(Request $request, string $token)
    {
        $report = $this->reportRepository->findByToken($token);
        if (!$report) abort(404);

        $isDraft = $request->input('action_type') === 'draft';

        // CEGAH RACE CONDITION & BYPASS TOMBOL BACK BROWSER
        // Jika laporan sudah ditangani, pastikan perangkat ini memiliki izin (telah memasukkan kode tiket).
        if ($report->status === 'Sudah penanganan') {
            if (!session()->has("unlocked_110_{$report->id}")) {
                return redirect()->route('pamapta.report.show', $token)
                    ->with('error', 'Akses ditolak: Laporan ini sudah diselesaikan sebelumnya. Anda harus memasukkan Kode Tiketing terlebih dahulu untuk mengubah data.');
            }
        }

        // Jika Draft, semua field bersifat nullable. Jika Complete, required.
        $rule = $isDraft ? 'nullable' : 'required';
        
        // Foto wajib saat complete jika belum pernah upload sebelumnya
        $fotoRule = $isDraft ? 'nullable|image|max:10240' : ($report->bukti_foto_path ? 'nullable|image|max:10240' : 'required|image|max:10240');

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'alamat' => 'nullable|string',
            'foto' => $fotoRule,
            'nama_pamapta' => "$rule|string",
            'nrp_pamapta' => "$rule|string",
            'uraian_kejadian' => "$rule|string",
            'modus_operandi' => 'nullable|string',
            'korban' => 'nullable|string',
            'pelaku' => 'nullable|string',
            'sanksi_sanksi' => 'nullable|string',
            'motif' => 'nullable|string',
            'alat_yang_digunakan' => 'nullable|string',
            'kerugian' => 'nullable|string',
            'bukti_yang_dapat_disita' => 'nullable|string',
            'tindakan_kepolisian' => 'nullable|string',
            'keterangan_lain' => 'nullable|string',
        ]);

        $path = $report->bukti_foto_path;

        // Upload photo jika ada
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('reports_110', 'public');

            // Dispatch watermark processing to background queue
            // so the HTTP response returns immediately
            ProcessReport110Watermark::dispatch($path, [
                'Nama' => $request->nama_pamapta ?? '-',
                'NRP' => $request->nrp_pamapta ?? '-',
                'Alamat' => $request->alamat ?? 'Tidak diketahui',
                'Koordinat' => "{$request->lat}, {$request->lng}",
                'Waktu' => now()->format('d-m-Y H:i:s'),
                'Tiket' => $report->no_tiketing
            ]);
        }

        // Prepare Update Data
        $updateData = $request->only([
            'nama_pamapta', 'nrp_pamapta', 'modus_operandi', 'korban',
            'uraian_kejadian', 'pelaku', 'sanksi_sanksi', 'motif',
            'alat_yang_digunakan', 'kerugian', 'bukti_yang_dapat_disita',
            'tindakan_kepolisian', 'keterangan_lain'
        ]);

        // Status diubah hanya jika complete
        if (!$isDraft) {
            $updateData['status'] = 'Sudah penanganan';
            $updateData['waktu_diselesaikan'] = now();
        } else {
            $updateData['status'] = 'Sedang penanganan';
        }

        $updateData['bukti_foto_path'] = $path;
        $updateData['alamat_aktual_110'] = $request->alamat;
        $updateData['koordinat_110'] = DB::raw("ST_GeomFromText('POINT({$request->lng} {$request->lat})', 4326)");

        $this->reportRepository->update($report, $updateData);

        if ($isDraft) {
            return back()->with('success', 'Draft laporan berhasil disimpan sementara.');
        }

        return back()->with('success', 'Laporan berhasil diselesaikan.');
    }
}
