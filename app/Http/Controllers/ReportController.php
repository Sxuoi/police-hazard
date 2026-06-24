<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\OperationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $operations = $this->operations->allActive();

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $rawQuery = DB::table('assignments as a')
            ->select('a.id as assignment_id', 'a.officer_id', 'a.location_id', 'a.operation_id', 'd.active_date')
            ->join(DB::raw("LATERAL (SELECT generate_series(GREATEST(a.start_date, ?::date), LEAST(COALESCE(a.end_date, CURRENT_DATE), ?::date), '1 day'::interval)::date as active_date) d"), function($join) {
                $join->on(DB::raw('1'), '=', DB::raw('1'));
            })
            ->addBinding($startDate, 'join')
            ->addBinding($endDate, 'join')
            ->where('a.status', '!=', 'cancelled')
            ->where('a.start_date', '<=', $endDate)
            ->where(function($q) use ($startDate) {
                $q->whereNull('a.end_date')
                  ->orWhere('a.end_date', '>=', $startDate);
            });

        if ($request->filled('operation_id')) {
            $rawQuery->where('a.operation_id', $request->operation_id);
        }

        if ($request->filled('officer')) {
            $officerSearch = strtolower($request->officer);
            $rawQuery->join('users as u', 'a.officer_id', '=', 'u.id')
                     ->where(function($q) use ($officerSearch) {
                         $q->where('u.name', 'ilike', "%{$officerSearch}%")
                           ->orWhere('u.nrp', 'ilike', "%{$officerSearch}%");
                     });
        }

        $paginator = $rawQuery->orderBy('d.active_date', 'desc')->paginate(50)->withQueryString();

        $assignmentIds = $paginator->pluck('assignment_id')->unique();
        $assignmentsMap = Assignment::with(['officer', 'location', 'operation'])
            ->whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        $officerIds = $paginator->pluck('officer_id')->unique();
        $attendancesMap = Attendance::whereIn('officer_id', $officerIds)
            ->whereBetween('checked_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->groupBy(function($att) {
                return $att->officer_id . '_' . $att->checked_in_at->format('Y-m-d');
            });

        $reports = $paginator->getCollection()->map(function($row) use ($assignmentsMap, $attendancesMap) {
            $assignment = $assignmentsMap->get($row->assignment_id);
            $attendance = $attendancesMap->get($row->officer_id . '_' . $row->active_date)?->first();

            return (object) [
                'date' => Carbon::parse($row->active_date),
                'assignment' => $assignment,
                'attendance' => $attendance,
                'status' => $attendance ? 'attended' : 'missed'
            ];
        });

        // Apply status filter in memory (since status depends on attendance existence)
        if ($request->filled('status')) {
            $reports = $reports->filter(fn($r) => $r->status === $request->status);
        }

        $paginator->setCollection($reports);

        return view('reports.index', compact('paginator', 'operations', 'startDate', 'endDate'));
    }

    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $rawQuery = DB::table('assignments as a')
            ->select('a.id as assignment_id', 'a.officer_id', 'a.location_id', 'a.operation_id', 'd.active_date')
            ->join(DB::raw("LATERAL (SELECT generate_series(GREATEST(a.start_date, ?::date), LEAST(COALESCE(a.end_date, CURRENT_DATE), ?::date), '1 day'::interval)::date as active_date) d"), function($join) {
                $join->on(DB::raw('1'), '=', DB::raw('1'));
            })
            ->addBinding($startDate, 'join')
            ->addBinding($endDate, 'join')
            ->where('a.status', '!=', 'cancelled')
            ->where('a.start_date', '<=', $endDate)
            ->where(function($q) use ($startDate) {
                $q->whereNull('a.end_date')
                  ->orWhere('a.end_date', '>=', $startDate);
            });

        if ($request->filled('operation_id')) {
            $rawQuery->where('a.operation_id', $request->operation_id);
        }

        if ($request->filled('officer')) {
            $officerSearch = strtolower($request->officer);
            $rawQuery->join('users as u', 'a.officer_id', '=', 'u.id')
                     ->where(function($q) use ($officerSearch) {
                         $q->where('u.name', 'ilike', "%{$officerSearch}%")
                           ->orWhere('u.nrp', 'ilike', "%{$officerSearch}%");
                     });
        }

        $rows = $rawQuery->orderBy('d.active_date', 'desc')->get();

        $assignmentIds = $rows->pluck('assignment_id')->unique();
        $assignmentsMap = Assignment::with(['officer', 'location', 'operation'])
            ->whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        $officerIds = $rows->pluck('officer_id')->unique();
        $attendancesMap = Attendance::whereIn('officer_id', $officerIds)
            ->whereBetween('checked_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->get()
            ->groupBy(function($att) {
                return $att->officer_id . '_' . $att->checked_in_at->format('Y-m-d');
            });

        $reports = $rows->map(function($row) use ($assignmentsMap, $attendancesMap) {
            $assignment = $assignmentsMap->get($row->assignment_id);
            $attendance = $attendancesMap->get($row->officer_id . '_' . $row->active_date)?->first();

            return (object) [
                'date' => Carbon::parse($row->active_date),
                'assignment' => $assignment,
                'attendance' => $attendance,
                'status' => $attendance ? 'attended' : 'missed'
            ];
        });

        if ($request->filled('status')) {
            $reports = $reports->filter(fn($r) => $r->status === $request->status);
        }

        $fileName = 'rekap_harian_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($reports) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8 Excel support
            fwrite($file, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($file, [
                'Tanggal',
                'Operasi',
                'Lokasi',
                'Waktu',
                'NRP',
                'Nama Petugas',
                'Status',
                'Waktu Hadir',
                'Foto'
            ]);

            // CSV Data
            foreach ($reports as $report) {
                $assignment = $report->assignment;
                $waktuString = $assignment->operation
                    ? substr($assignment->operation->start_time, 0, 5) . ' - ' . ($assignment->operation->end_time ? substr($assignment->operation->end_time, 0, 5) : '23:59')
                    : '-';
                fputcsv($file, [
                    $report->date->format('Y-m-d'),
                    $assignment->operation->name ?? '-',
                    $assignment->location->name ?? '-',
                    $waktuString,
                    $assignment->officer->nrp ?? '-',
                    $assignment->officer->name ?? '-',
                    strtoupper($report->status),
                    $report->attendance ? $report->attendance->checked_in_at->format('H:i:s') : '-',
                    $report->attendance && $report->attendance->photo_path ? route('dashboard.attendances.photo', $report->attendance->id) : '-'
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}
