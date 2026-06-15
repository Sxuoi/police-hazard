<?php

namespace App\Http\Controllers;

use App\Actions\AssignOfficerToLocationAction;
use App\Models\Saker;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\OperationRepositoryInterface;
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
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'assigned_saker_id' => ['required', 'uuid', 'exists:sakers,id'],
        ]);

        $location = $this->locations->findOrFail($validated['location_id']);
        $operation = $this->operations->findOrFail($validated['operation_id']);

        foreach ($validated['officer_ids'] as $officerId) {
            $this->assignOfficer->execute(
                data: array_merge($validated, [
                    'officer_id' => $officerId,
                    'saker_id' => $location->saker_id,
                ]),
                actor: $request->user(),
            );
        }

        $count = count($validated['officer_ids']);

        return redirect()->route('assignments.index')
            ->with('success', "{$count} penugasan berhasil dibuat.");
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

    public function officerSearch(Request $request): JsonResponse
    {
        $officers = $this->users->searchOfficers($request->get('q', ''));

        return response()->json($officers);
    }
}
