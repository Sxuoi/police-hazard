<?php

namespace App\Http\Controllers\Api\V1\Officer;

use App\Http\Controllers\Controller;
use App\Models\Concerns\SakerScope;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Officer API — Assignment controller (R2).
 * Returns the authenticated officer's assignments, assignment detail,
 * and geofence distance for a location.
 */
class AssignmentController extends Controller
{
    /**
     * GET /api/v1/officer/assignments (R2.1–R2.7, R2.11).
     */
    public function index(Request $request, AssignmentRepositoryInterface $repo, AttendanceRepositoryInterface $attRepo): JsonResponse
    {
        $officer = $request->user();
        $timezone = config('policehazard.default_timezone', 'Asia/Jakarta');

        $dateParam = $request->query('date');

        if ($dateParam !== null) {
            try {
                $target = Carbon::createFromFormat('Y-m-d', (string) $dateParam, $timezone);
                if ($target === false || $target->format('Y-m-d') !== $dateParam) {
                    $this->invalidDateRange();
                }
            } catch (\Throwable) {
                $this->invalidDateRange();
            }

            $today = Carbon::today($timezone);
            $diffDays = $today->diffInDays($target, false);

            if (abs($diffDays) > 7) {
                $this->invalidDateRange();
            }

            $from = $target->copy()->startOfDay();
            $to = $target->copy()->endOfDay();
        } else {
            $today = Carbon::today($timezone);
            $from = $today->copy()->startOfDay();
            $to = $today->copy()->endOfDay();
        }

        $assignments = $repo->listForOfficer(
            $officer->id,
            $officer->saker_id,
            $from,
            $to,
        );

        $assignments->loadMissing([
            'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            'location.zone' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
        ]);

        $coordsByLocation = $this->loadLocationCoordinates(
            $assignments->pluck('location_id')->filter()->unique()->values()->all()
        );

        $data = $assignments->map(function ($assignment) use ($attRepo, $coordsByLocation, $target, $today) {
            $checkDate = isset($target) ? $target->format('Y-m-d') : $today->format('Y-m-d');
            return $this->toAssignmentArray(
                $assignment,
                $attRepo->verifiedExistsFor($assignment->id, $checkDate),
                $coordsByLocation[$assignment->location_id] ?? null,
            );
        })->values();

        return response()->json(['data' => $data], 200);
    }

    /**
     * GET /api/v1/officer/assignments/{id} (R2.8, R2.9).
     */
    public function show(
        string $id,
        Request $request,
        AssignmentRepositoryInterface $repo,
        AttendanceRepositoryInterface $attRepo,
    ): JsonResponse {
        $officer = $request->user();

        $assignment = $repo->find($id);

        // R2.9 — 404 when not found or not owned.
        if (
            ! $assignment
            || $assignment->officer_id !== $officer->id
        ) {
            return $this->assignmentNotFound();
        }

        $assignment->loadMissing([
            'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            'location.padal' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            'location.zone' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
            'operation' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
        ]);

        $coords = $this->loadLocationCoordinates([$assignment->location_id])[$assignment->location_id] ?? null;
        $base = $this->toAssignmentArray(
            $assignment,
            $attRepo->verifiedExistsFor($assignment->id, Carbon::today(config('policehazard.default_timezone', 'Asia/Jakarta'))->format('Y-m-d')),
            $coords,
        );

        // R2.8 additions.
        $padal = $assignment->location?->padal ?? null;
        $base['padal_name'] = $padal?->name;
        $base['padal_phone'] = $padal?->phone;
        $base['operating_hours'] = $assignment->location?->operating_hours;

        return response()->json(['data' => $base], 200);
    }

    /**
     * GET /api/v1/officer/assignments/{id}/distance?latitude=&longitude= (R2.10).
     */
    public function distance(
        string $id,
        Request $request,
        AssignmentRepositoryInterface $repo,
        GeofenceService $geofence,
    ): JsonResponse {
        $officer = $request->user();

        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $assignment = $repo->find($id);

        if (
            ! $assignment
            || $assignment->officer_id !== $officer->id
        ) {
            return $this->assignmentNotFound();
        }

        $assignment->loadMissing([
            'location' => fn ($q) => $q->withoutGlobalScopes([SakerScope::class]),
        ]);
        $location = $assignment->location;

        $lat = (float) $request->query('latitude');
        $lng = (float) $request->query('longitude');

        $distance = $geofence->distanceFromLocation($location, $lat, $lng);
        $within = $geofence->isWithinGeofence($location, $lat, $lng);

        return response()->json([
            'distance_meters' => $distance,
            'within_geofence' => $within,
        ], 200);
    }

    /**
     * Serialize an Assignment model into the R2.6 shape.
     */
    private function toAssignmentArray($assignment, bool $alreadyCheckedIn, ?array $coords): array
    {
        $location = $assignment->location;
        $operation = $assignment->operation;

        return [
            'assignment_id' => $assignment->id,
            'operation_name' => $operation?->name,
            'operation_type' => $operation?->operation_type,
            'zone_name' => $location?->zone?->name,
            'location_id' => $location?->id,
            'location_name' => $location?->name,
            'location_address' => $location?->address,
            'location_coordinates' => $coords,
            'location_radius_meters' => $location?->radius_meters,
            'location_timezone' => $location?->timezone,
            'shift_id' => $operation?->id,
            'shift_name' => $operation?->name,
            'shift_start' => $operation?->start_time,
            'shift_end' => $operation?->end_time ?? '23:59:00',
            'start_date' => optional($assignment->start_date)->format('Y-m-d'),
            'end_date' => optional($assignment->end_date)->format('Y-m-d'),
            'assignment_date' => optional($assignment->start_date)->format('Y-m-d'),
            'status' => $assignment->status,
            'already_checked_in' => $alreadyCheckedIn,
        ];
    }

    /**
     * Fetch lat/lng for a batch of location UUIDs via PostGIS.
     *
     * @param  array<int, string>  $locationIds
     * @return array<string, array{lat: float, lng: float}>
     */
    private function loadLocationCoordinates(array $locationIds): array
    {
        if (empty($locationIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($locationIds), '?'));

        $rows = DB::select(
            "SELECT id, ST_Y(coordinates::geometry) AS lat, ST_X(coordinates::geometry) AS lng
             FROM locations WHERE id IN ({$placeholders})",
            $locationIds,
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->id] = [
                'lat' => (float) $row->lat,
                'lng' => (float) $row->lng,
            ];
        }

        return $out;
    }

    private function invalidDateRange(): never
    {
        abort(response()->json([
            'type' => 'https://policehazard.local/errors/INVALID_DATE_RANGE',
            'title' => 'Rentang tanggal tidak valid',
            'status' => 422,
            'detail' => 'date harus dalam format YYYY-MM-DD dan dalam ±7 hari dari hari ini.',
            'reason_code' => 'INVALID_DATE_RANGE',
            'bypass_eligible' => false,
            'request_id' => request()->attributes->get('request_id'),
        ], 422, ['Content-Type' => 'application/problem+json']));
    }

    private function assignmentNotFound(): JsonResponse
    {
        return response()->json([
            'type' => 'https://policehazard.local/errors/ASSIGNMENT_NOT_FOUND',
            'title' => 'Penugasan tidak ditemukan',
            'status' => 404,
            'detail' => 'Penugasan tidak ditemukan untuk petugas ini.',
            'reason_code' => 'ASSIGNMENT_NOT_FOUND',
            'bypass_eligible' => false,
            'request_id' => request()->attributes->get('request_id'),
        ], 404, ['Content-Type' => 'application/problem+json']);
    }
}
