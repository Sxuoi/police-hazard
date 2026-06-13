<?php

namespace App\Http\Controllers\Api\V1\Officer;

use App\Actions\CreateBypassRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Officer\BypassRequestRequest;
use App\Models\Attendance;
use App\Models\ManualBypassApproval;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Support\Dtos\BypassRequestDto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Officer API — Manual bypass request controller (R4).
 * Creates bypass requests and exposes status polling for the authenticated officer.
 */
class BypassRequestController extends Controller
{
    /**
     * POST /api/v1/officer/bypass-request (R4.2–R4.11).
     */
    public function store(BypassRequestRequest $request, CreateBypassRequestAction $action): JsonResponse
    {
        $validated = $request->validated();
        $officer = $request->user();

        $dto = new BypassRequestDto(
            assignmentId: $validated['assignment_id'],
            reasonCode: $validated['reason_code'],
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            gpsAccuracy: (float) $validated['gps_accuracy'],
            gpsAltitude: isset($validated['gps_altitude']) ? (float) $validated['gps_altitude'] : null,
            gpsSpeed: isset($validated['gps_speed']) ? (float) $validated['gps_speed'] : null,
            gpsProvider: $validated['gps_provider'],
            timestampDevice: Carbon::parse($validated['timestamp_device']),
            mockLocation: (bool) $validated['mock_location'],
            photo: $request->file('photo'),
            officerNote: $validated['officer_note'],
            deviceMetadata: $validated['device_metadata'] ?? null,
        );

        $bypass = $action($dto, $officer);

        return response()->json([
            'id' => $bypass->id,
            'status' => $bypass->status,
            'expires_at' => $bypass->expires_at?->toIso8601String(),
        ], 201);
    }

    /**
     * GET /api/v1/officer/bypass-request/{id} (R4.12).
     */
    public function show(
        string $id,
        Request $request,
        ManualBypassApprovalRepositoryInterface $repo,
    ): JsonResponse {
        $officer = $request->user();

        $bypass = ManualBypassApproval::withoutGlobalScopes()
            ->where('id', $id)
            ->where('officer_id', $officer->id)
            ->where('saker_id', $officer->saker_id)
            ->first();

        if (! $bypass) {
            return $this->bypassNotFound();
        }

        $attendanceId = null;
        if ($bypass->status === 'approved') {
            $attendanceId = Attendance::where('bypass_approval_id', $bypass->id)->value('id');
        }

        return response()->json([
            'id' => $bypass->id,
            'status' => $bypass->status,
            'bypass_reason' => $bypass->bypass_reason,
            'officer_note' => $bypass->officer_note,
            'reviewer_note' => $bypass->reviewer_note,
            'expires_at' => $bypass->expires_at?->toIso8601String(),
            'created_at' => $bypass->created_at?->toIso8601String(),
            'reviewed_at' => $bypass->reviewed_at?->toIso8601String(),
            'attendance_id' => $attendanceId,
        ], 200);
    }

    private function bypassNotFound(): JsonResponse
    {
        return response()->json([
            'type' => 'https://policehazard.local/errors/BYPASS_NOT_FOUND',
            'title' => 'Permintaan bypass tidak ditemukan',
            'status' => 404,
            'detail' => 'Permintaan bypass tidak ditemukan untuk petugas ini.',
            'reason_code' => 'BYPASS_NOT_FOUND',
            'bypass_eligible' => false,
            'request_id' => request()->attributes->get('request_id'),
        ], 404, ['Content-Type' => 'application/problem+json']);
    }
}
