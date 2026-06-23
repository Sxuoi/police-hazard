<?php

namespace App\Http\Controllers\Api\V1\Officer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Officer API — Attendance history controller (R6).
 * Paginated self-service history for the authenticated officer + single detail view.
 */
class AttendanceHistoryController extends Controller
{
    /**
     * GET /api/v1/officer/attendance/history (R6.1–R6.3, R6.5).
     */
    public function index(Request $request, AttendanceRepositoryInterface $repo): JsonResponse
    {
        $officer = $request->user();
        $timezone = config('policehazard.default_timezone', 'Asia/Jakarta');

        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $to = $request->query('to')
            ? Carbon::createFromFormat('Y-m-d', (string) $request->query('to'), $timezone)
            : Carbon::today($timezone);

        $from = $request->query('from')
            ? Carbon::createFromFormat('Y-m-d', (string) $request->query('from'), $timezone)
            : $to->copy()->subDays(30);

        $page = (int) ($request->query('page') ?? 1);

        $paginator = $repo->listForOfficerHistory(
            $officer->id,
            $from,
            $to,
            $page,
        );

        $data = collect($paginator->items())->map(
            fn (Attendance $att) => $this->toAttendanceArray($att, $repo),
        )->values();

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ], 200);
    }

    /**
     * GET /api/v1/officer/attendance/{id} (R6.4, R6.5).
     */
    public function show(string $id, Request $request, AttendanceRepositoryInterface $repo): JsonResponse
    {
        $officer = $request->user();

        $attendance = $repo->findForOfficer($id, $officer->id);

        if (! $attendance || $attendance->saker_id !== $officer->saker_id) {
            return $this->attendanceNotFound();
        }

        return response()->json([
            'data' => $this->toAttendanceArray($attendance, $repo, detailed: true),
        ], 200);
    }

    private function toAttendanceArray(Attendance $att, AttendanceRepositoryInterface $repo, bool $detailed = false): array
    {
        $location = $att->location;

        $photoUrl = null;
        if ($att->photo_status === 'processed' && $att->photo_path) {
            try {
                $photoUrl = $repo->presignPhotoUrl($att->id);
            } catch (\Throwable) {
                $photoUrl = null;
            }
        }

        $base = [
            'attendance_id' => $att->id,
            'assignment_id' => $att->assignment_id,
            'location_name' => $location?->name,
            'location_timezone' => $location?->timezone,
            'checked_in_at' => $att->checked_in_at?->toIso8601String(),
            'distance_from_point' => $att->distance_from_point,
            'status' => $att->status,
            'is_manual_bypass' => $att->is_manual_bypass,
            'photo_status' => $att->photo_status,
            'photo_url' => $photoUrl,
        ];

        if ($detailed) {
            $base['shift_window_start'] = $att->shift_window_start?->toIso8601String();
            $base['shift_window_end'] = $att->shift_window_end?->toIso8601String();
            $base['gps_accuracy_meters'] = $att->gps_accuracy_meters;
            $base['is_within_geofence'] = $att->is_within_geofence;
            $base['is_within_shift'] = $att->is_within_shift;
            $base['spoofing_score'] = $att->spoofing_score;
            $base['spoofing_signals'] = $att->spoofing_signals;
        }

        return $base;
    }

    private function attendanceNotFound(): JsonResponse
    {
        return response()->json([
            'type' => 'https://policehazard.local/errors/ATTENDANCE_NOT_FOUND',
            'title' => 'Absensi tidak ditemukan',
            'status' => 404,
            'detail' => 'Data absensi tidak ditemukan untuk petugas ini.',
            'reason_code' => 'ATTENDANCE_NOT_FOUND',
            'bypass_eligible' => false,
            'request_id' => request()->attributes->get('request_id'),
        ], 404, ['Content-Type' => 'application/problem+json']);
    }
}
