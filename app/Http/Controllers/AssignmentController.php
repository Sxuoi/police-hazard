<?php

namespace App\Http\Controllers;

use App\Actions\AssignOfficerToLocationAction;
use App\Models\Saker;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\OperationRepositoryInterface;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\ZoneRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignments,
        private readonly OperationRepositoryInterface $operations,
        private readonly ZoneRepositoryInterface $zones,
        private readonly LocationRepositoryInterface $locations,
        private readonly ShiftRepositoryInterface $shifts,
        private readonly UserRepositoryInterface $users,
        private readonly AssignOfficerToLocationAction $assignOfficer,
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $assignments = $this->assignments->paginate(
            perPage: 20,
            filters: $request->only(['operation_id', 'location_id', 'officer_id', 'date', 'status']),
        );

        $operations = $this->operations->allActive();

        return view('assignments.index', compact('assignments', 'operations'));
    }

    public function create(): View
    {
        $operations = $this->operations->allActive();
        $sakers = Saker::where('is_active', true)->get();

        return view('assignments.create', compact('operations', 'sakers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'officer_ids' => ['required', 'array', 'min:1'],
            'officer_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'operation_id' => ['required', 'uuid', 'exists:operations,id'],
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date'],
            'assigned_saker_id' => ['required', 'uuid', 'exists:sakers,id'],
        ]);

        $location = $this->locations->findOrFail($validated['location_id']);
        $operation = $this->operations->findOrFail($validated['operation_id']);

        // The wizard no longer asks for a shift — the operation's start/end
        // time is the canonical window. Resolve (or create) a single shift
        // on the chosen location that mirrors that window so the existing
        // assignments → shifts FK chain (and the check-in pipeline that
        // reads shift.shift_start / shift.shift_end) keeps working.
        $shift = $this->resolveShiftForOperation($operation, $location->id);
        $validated['shift_id'] = $shift->id;

        foreach ($validated['officer_ids'] as $officerId) {
            $this->assignOfficer->execute(
                data: array_merge($validated, [
                    'officer_id' => $officerId,
                    'saker_id' => $location->saker_id,
                ]),
                actor: $request->user(),
            );
        }

        $count = count($validated['dates']) * count($validated['officer_ids']);

        return redirect()->route('assignments.index')
            ->with('success', "{$count} penugasan berhasil dibuat.");
    }

    /**
     * Find or create a shift on the given location whose window matches the
     * operation's start_time / end_time. Defaults end_time to 23:59 when the
     * operation has no end_time set.
     */
    private function resolveShiftForOperation(\App\Models\Operation $operation, string $locationId): \App\Models\Shift
    {
        $start = substr((string) $operation->start_time, 0, 8);
        $end = $operation->end_time
            ? substr((string) $operation->end_time, 0, 8)
            : '23:59:00';

        // Existing match? Use it. Match on time-of-day strings to avoid
        // any TZ subtleties (the schema stores TIME, not TIMESTAMP).
        $existing = \App\Models\Shift::where('location_id', $locationId)
            ->where('shift_start', $start)
            ->where('shift_end', $end)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->shifts->create([
            'location_id' => $locationId,
            'name' => $operation->name.' — '.substr($start, 0, 5).'–'.substr($end, 0, 5),
            'shift_start' => $start,
            'shift_end' => $end,
            'active_days' => [1, 2, 3, 4, 5, 6, 7],
            'is_active' => true,
        ]);
    }

    public function show(string $id): View
    {
        $assignment = $this->assignments->findOrFail($id);

        return view('assignments.show', compact('assignment'));
    }

    public function cancel(Request $request, string $id): RedirectResponse
    {
        $assignment = $this->assignments->findOrFail($id);

        $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $assignment->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->reason,
        ]);

        $this->auditService->log('ASSIGNMENT_CANCELLED', $assignment, [
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'Penugasan berhasil dibatalkan.');
    }

    // ── Ajax helpers for multi-step wizard ───────────────────────────

    public function zonesByOperation(Request $request): JsonResponse
    {
        $zones = $this->zones->byOperation($request->operation_id);

        return response()->json($zones);
    }

    public function locationsByZone(Request $request): JsonResponse
    {
        $locations = $this->locations->byZone($request->zone_id);

        return response()->json($locations);
    }

    public function shiftsByLocation(Request $request): JsonResponse
    {
        $shifts = $this->shifts->byLocation($request->location_id);

        return response()->json($shifts);
    }

    public function officerSearch(Request $request): JsonResponse
    {
        $officers = $this->users->searchOfficers($request->get('q', ''));

        return response()->json($officers);
    }
}
