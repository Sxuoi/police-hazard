<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\OperationRepositoryInterface;
use App\Repositories\Contracts\ZoneRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Ramsey\Uuid\Uuid;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationRepositoryInterface $locations,
        private readonly OperationRepositoryInterface $operations,
        private readonly ZoneRepositoryInterface $zones,
        private readonly AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $locations = $this->locations->paginate(
            perPage: 15,
            filters: $request->only(['zone_id', 'operation_id', 'search']),
        );

        return view('locations.index', compact('locations'));
    }

    public function create(): View
    {
        $operations = $this->operations->allActive();

        return view('locations.create', compact('operations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'zone_id' => ['required', 'uuid', 'exists:zones,id'],
            'name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'between:10,500'],
            'minimum_officer' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $zone = $this->zones->findOrFail($validated['zone_id']);
        $id = Uuid::uuid7()->toString();

        DB::statement('
            INSERT INTO locations (id, zone_id, saker_id, name, address, coordinates, radius_meters, minimum_officer, coords_locked, is_active, created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, false, true, ?, ?, NOW(), NOW())
        ', [
            $id,
            $zone->id,
            $zone->saker_id,
            $validated['name'],
            $validated['address'] ?? null,
            $validated['longitude'],
            $validated['latitude'],
            $validated['radius_meters'],
            $validated['minimum_officer'],
            $request->user()->id,
            $request->user()->id,
        ]);

        $location = $this->locations->find($id);

        // Auto-create a shift for the new location whose window mirrors the
        // parent operation's start_time / end_time. This keeps shifts in
        // sync with the operation so admins don't have to pick a shift on
        // the assignment wizard, and the check-in pipeline (which reads
        // shift.shift_start / shift.shift_end) gets the right window.
        $operation = $zone->operation;
        $shiftStart = $operation && $operation->start_time
            ? substr((string) $operation->start_time, 0, 8)
            : '00:00:00';
        $shiftEnd = $operation && $operation->end_time
            ? substr((string) $operation->end_time, 0, 8)
            : '23:59:59';

        // Operations may legitimately have start == end (treat as 24h).
        // The shifts CHECK constraint forbids zero-length, so collapse to
        // a 24-hour fallback in that case.
        if ($shiftStart === $shiftEnd) {
            $shiftStart = '00:00:00';
            $shiftEnd = '23:59:59';
        }

        $shiftName = $operation
            ? $operation->name.' — '.substr($shiftStart, 0, 5).'–'.substr($shiftEnd, 0, 5)
            : 'Shift Utama';

        DB::statement('
            INSERT INTO shifts (id, location_id, name, shift_start, shift_end, active_days, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, \'{1,2,3,4,5,6,7}\', true, NOW(), NOW())
        ', [
            Uuid::uuid7()->toString(),
            $location->id,
            $shiftName,
            $shiftStart,
            $shiftEnd,
        ]);

        $this->auditService->log('LOCATION_CREATED', $location, [
            'name' => $location->name,
            'coordinates' => [$validated['latitude'], $validated['longitude']],
        ]);

        return redirect()->route('locations.show', $location)
            ->with('success', 'Lokasi berhasil dibuat.');
    }

    public function show(string $id): View
    {
        $location = $this->locations->findOrFail($id);
        $location->load(['zone.operation', 'shifts']);

        return view('locations.show', compact('location'));
    }

    public function edit(string $id): View
    {
        $location = $this->locations->findOrFail($id);

        // Fetch raw coordinates using PostGIS
        $coords = DB::selectOne('SELECT ST_Y(coordinates::geometry) as lat, ST_X(coordinates::geometry) as lng FROM locations WHERE id = ?', [$id]);
        $location->lat = $coords->lat ?? '';
        $location->lng = $coords->lng ?? '';

        $operations = $this->operations->allActive();

        return view('locations.edit', compact('location', 'operations'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $location = $this->locations->findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'radius_meters' => ['required', 'integer', 'between:10,500'],
            'minimum_officer' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];

        // Coordinates are ONLY editable if not locked (PRD §7.5)
        if (! $location->coords_locked) {
            $rules['latitude'] = ['required', 'numeric', 'between:-90,90'];
            $rules['longitude'] = ['required', 'numeric', 'between:-180,180'];
        }

        $validated = $request->validate($rules);

        $updates = [
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $location->address,
            'radius_meters' => $validated['radius_meters'],
            'minimum_officer' => $validated['minimum_officer'],
            'description' => $validated['description'] ?? $location->description,
            'updated_by' => $request->user()->id,
        ];

        $location->update($updates);

        // Update coordinates if not locked
        if (! $location->coords_locked && isset($validated['latitude'])) {
            DB::statement(
                'UPDATE locations SET coordinates = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
                [$validated['longitude'], $validated['latitude'], $location->id]
            );
        }

        $this->auditService->log('LOCATION_UPDATED', $location, ['changes' => $location->getChanges()]);

        return redirect()->route('locations.show', $location)
            ->with('success', 'Lokasi berhasil diperbarui.');
    }
}
