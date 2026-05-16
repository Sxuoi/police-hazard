<?php

namespace App\Http\Controllers\Api\V1\Officer;

use App\Actions\ProcessCheckinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Officer\CheckinRequest;
use App\Support\Dtos\CheckinDto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Officer API — Check-in controller (R3).
 * Builds the CheckinDto from the validated payload + authenticated officer
 * context, invokes ProcessCheckinAction, and returns the attendance summary.
 */
class CheckinController extends Controller
{
    /**
     * POST /api/v1/officer/checkin (R3.1, R3.15).
     */
    public function store(CheckinRequest $request, ProcessCheckinAction $action): JsonResponse
    {
        $validated = $request->validated();
        $officer = $request->user();

        $now = Carbon::now();

        $dto = new CheckinDto(
            assignmentId: $validated['assignment_id'],
            officerId: $officer->id,
            // Resolved by the action via assignment lookup; leave blank for now.
            locationId: '',
            sakerId: $officer->saker_id,
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            gpsAccuracy: (float) $validated['gps_accuracy'],
            gpsAltitude: isset($validated['gps_altitude']) ? (float) $validated['gps_altitude'] : null,
            gpsSpeed: isset($validated['gps_speed']) ? (float) $validated['gps_speed'] : null,
            gpsProvider: $validated['gps_provider'],
            timestampDevice: Carbon::parse($validated['timestamp_device']),
            mockLocation: (bool) $validated['mock_location'],
            photo: $request->file('photo'),
            checkedInAt: $now,
            // These are finalized inside the action; seeded with safe defaults.
            isWithinGeofence: false,
            isWithinShift: false,
            distanceFromPoint: 0.0,
            spoofingScore: 0,
            spoofingSignals: [],
            deviceMetadata: $validated['device_metadata'] ?? null,
            shiftWindowStart: $now,
            shiftWindowEnd: $now,
            status: 'verified',
        );

        $attendance = $action($dto);

        return response()->json([
            'attendance_id' => $attendance->id,
            'checked_in_at' => $attendance->checked_in_at?->toIso8601String(),
            'distance_from_point' => $attendance->distance_from_point,
            'is_flagged' => $attendance->status === 'flagged',
            'photo_status' => $attendance->photo_status,
        ], 200);
    }
}
