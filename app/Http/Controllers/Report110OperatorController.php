<?php

namespace App\Http\Controllers;

use App\Actions\CreateReport110Action;
use App\Http\Requests\StoreReport110Request;
use App\Models\Report110;
use App\Repositories\Contracts\Report110RepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\JenisGangguanRepositoryInterface;
use Illuminate\Http\Request;

class Report110OperatorController extends Controller
{
    public function __construct(
        protected Report110RepositoryInterface $reportRepository,
        protected UnitRepositoryInterface $unitRepository,
        protected JenisGangguanRepositoryInterface $jenisGangguanRepository
    ) {}

    public function index()
    {
        $reports = $this->reportRepository->paginate(15);
        $units = $this->unitRepository->getAll();
        $jenisGangguans = $this->jenisGangguanRepository->getAll();
        
        return view('operator_110.index', compact('reports', 'units', 'jenisGangguans'));
    }

    public function store(StoreReport110Request $request, CreateReport110Action $action)
    {
        try {
            $data = $request->validated();
            $data['saker_id'] = auth()->user()->saker_id ?? auth()->id();
            $report = $action->execute($data);

            return redirect()->route('operator-110.show', $report->id)
                ->with('success', 'Laporan 110 berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        $report = $this->reportRepository->findById($id);
        
        if (!$report) {
            abort(404);
        }

        // Generate WA Deep Link
        $waLink = $this->generateWhatsAppLink($report);

        $units = $this->unitRepository->getAll();
        $jenisGangguans = $this->jenisGangguanRepository->getAll();

        return view('operator_110.show', compact('report', 'waLink', 'units', 'jenisGangguans'));
    }

    public function update(\App\Http\Requests\UpdateReport110Request $request, string $id, \App\Services\WatermarkService $watermarkService)
    {
        try {
            $report = $this->reportRepository->findById($id);
            if (!$report) {
                abort(404);
            }

            if ($report->saker_id !== auth()->user()->saker_id) {
                abort(403, 'Unauthorized action.');
            }

            if ($report->status !== 'Sudah penanganan') {
                abort(403, 'Laporan hanya dapat diubah jika statusnya telah diselesaikan.');
            }

            $updateData = $request->validated();

            if ($request->hasFile('foto')) {
                // Hapus file foto lama dari disk agar tidak menjadi file sampah (orphan)
                if ($report->bukti_foto_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($report->bukti_foto_path);
                }

                $path = $request->file('foto')->store('reports_110', 'public');
                $fullPath = storage_path('app/public/' . $path);

                $watermarkData = [
                    'Nama' => $request->nama_pamapta ?? $report->nama_pamapta,
                    'NRP' => $request->nrp_pamapta ?? $report->nrp_pamapta,
                    'Alamat' => $report->alamat_aktual_110 ?? 'Tidak diketahui',
                    'Koordinat' => $report->lat && $report->lng ? "{$report->lat}, {$report->lng}" : '-',
                    'Waktu' => $report->updated_at ? $report->updated_at->format('d-m-Y H:i:s') : now()->format('d-m-Y H:i:s'),
                    'Tiket' => $report->no_tiketing
                ];

                $watermarkService->applyWatermark($fullPath, $watermarkData);

                $updateData['bukti_foto_path'] = $path;
                unset($updateData['foto']);
            }

            $this->reportRepository->update($report, $updateData);

            return back()->with('success', 'Laporan 110 berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui laporan: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, string $id)
    {
        $report = $this->reportRepository->findById($id);
        
        if (!$report) {
            abort(404);
        }

        if ($report->saker_id !== auth()->user()->saker_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->input('kode_tiket') !== $report->no_tiketing) {
            return back()->with('error', 'Kode tiket tidak cocok, laporan gagal dihapus.');
        }

        try {
            // Hapus file foto dari disk agar tidak tertinggal di server
            if ($report->bukti_foto_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($report->bukti_foto_path);
            }

            $this->reportRepository->delete($report);
            return redirect()->route('operator-110.index')->with('success', 'Laporan 110 berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus laporan: ' . $e->getMessage());
        }
    }

    public function monitor()
    {
        $activeReports = $this->reportRepository->getActiveReports();
        
        // View global monitor map
        return view('operator_110.monitor', compact('activeReports'));
    }

    /**
     * Generate dynamic WhatsApp text template and URL.
     */
    protected function generateWhatsAppLink(Report110 $report): string
    {
        $unit = $report->unit;
        if (!$unit || empty($unit->no_wa)) {
            return '#'; // Fallback if no WA number or no unit
        }

        $linkPamapta = route('pamapta.report.show', ['token' => $report->token]);
        
        $waktuKejadian = $report->waktu_kejadian ? $report->waktu_kejadian->format('d-m-Y H:i') : '-';

        $text = "*LAPORAN 110 MASUK*\n\n";
        $text .= "Kepada Yth. {$unit->nama_unit},\n";
        $text .= "Mohon segera menindaklanjuti laporan masyarakat berikut:\n\n";
        $text .= "*No Tiketing*: {$report->no_tiketing}\n";
        if ($report->nama_pelapor) {
            $text .= "*Pelapor*: {$report->nama_pelapor}\n";
            $text .= "*No HP Pelapor*: {$report->no_hp_pelapor} ({$report->jenis_no_hp_pelapor})\n";
        }
        $text .= "*Jenis Gangguan*: {$report->jenis_gangguan}\n";
        $text .= "*TKP*: {$report->tempat_kejadian}\n";
        $text .= "*Waktu Kejadian*: {$waktuKejadian}\n\n";
        $text .= "Segera meluncur ke TKP dan isi laporan hasil penanganan melalui link khusus di bawah ini:\n";
        $text .= "{$linkPamapta}\n\n";
        $text .= "_Gunakan Kode Tiketing ({$report->no_tiketing}) sebagai verifikasi._";

        $urlencodedText = urlencode($text);
        
        // Ensure no_wa is formatted correctly by extracting numbers
        $noWa = preg_replace('/[^0-9]/', '', $unit->no_wa);

        return "https://wa.me/{$noWa}?text={$urlencodedText}";
    }
}
