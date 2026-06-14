<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\OperationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignments,
        private readonly OperationRepositoryInterface $operations
    ) {}

    public function index(Request $request): View
    {
        // Provide filters to the view
        $operations = $this->operations->allActive();

        $filters = $request->only(['operation_id', 'start_date', 'end_date', 'status']);

        $assignments = $this->assignments->paginate(
            perPage: 50,
            filters: $filters
        );

        return view('reports.index', compact('assignments', 'operations'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['operation_id', 'start_date', 'end_date', 'status']);

        // Fetch all matching assignments without pagination
        $query = Assignment::with(['officer', 'location', 'operation', 'saker']);

        if (isset($filters['operation_id'])) {
            $query->where('operation_id', $filters['operation_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['start_date'])) {
            $query->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $filters['start_date']));
        }
        if (isset($filters['end_date'])) {
            $query->where('start_date', '<=', $filters['end_date']);
        }

        // Saker scoping is handled globally by SakerScope for non-God admins,
        // but let's ensure it's explicitly ordered
        $assignments = $query->orderBy('start_date', 'desc')->get();

        $fileName = 'rekap_penugasan_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($assignments) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8 Excel support
            fwrite($file, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($file, [
                'ID Penugasan',
                'Tanggal',
                'Operasi',
                'Lokasi',
                'Waktu',
                'NRP',
                'Nama Petugas',
                'Status',
                'Waktu Hadir',
            ]);

            // CSV Data
            foreach ($assignments as $assignment) {
                $dateString = $assignment->start_date->format('Y-m-d') . ($assignment->end_date ? ' s/d ' . $assignment->end_date->format('Y-m-d') : ' - Selesai');
                $waktuString = $assignment->operation
                    ? substr($assignment->operation->start_time, 0, 5) . ' - ' . ($assignment->operation->end_time ? substr($assignment->operation->end_time, 0, 5) : '23:59')
                    : '-';
                fputcsv($file, [
                    $assignment->id,
                    $dateString,
                    $assignment->operation->name ?? '-',
                    $assignment->location->name ?? '-',
                    $waktuString,
                    $assignment->officer->nrp ?? '-',
                    $assignment->officer->name ?? '-',
                    strtoupper($assignment->status),
                    $assignment->attended_at ? $assignment->attended_at->format('H:i:s') : '-',
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}
