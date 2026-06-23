<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $officers = User::where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'nrp', 'phone', 'saker_id']);

        return view('locations.create', compact('operations', 'officers'));
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
            'padal_id' => ['nullable', 'uuid', 'exists:users,id'],
            'description' => ['nullable', 'string'],
        ]);

        $zone = $this->zones->findOrFail($validated['zone_id']);
        $id = Uuid::uuid7()->toString();

        DB::statement('
            INSERT INTO locations (id, zone_id, saker_id, name, address, coordinates, radius_meters, minimum_officer, padal_id, coords_locked, is_active, created_by, updated_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?, false, true, ?, ?, NOW(), NOW())
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
            $validated['padal_id'] ?? null,
            $request->user()->id,
            $request->user()->id,
        ]);

        $location = $this->locations->find($id);

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
        $location->load([
            'zone.operation',
            'padal',
            'assignments' => function ($query) {
                $query->where('status', 'active')->with('officer');
            }
        ]);

        return view('locations.show', compact('location'));
    }

    public function edit(string $id): View
    {
        $location = $this->locations->findOrFail($id);
        $location->load('zone');

        // Fetch raw coordinates using PostGIS
        $coords = DB::selectOne('SELECT ST_Y(coordinates::geometry) as lat, ST_X(coordinates::geometry) as lng FROM locations WHERE id = ?', [$id]);
        $location->lat = $coords->lat ?? '';
        $location->lng = $coords->lng ?? '';

        $operations = $this->operations->allActive();
        $officers = User::where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'nrp', 'phone', 'saker_id']);

        return view('locations.edit', compact('location', 'operations', 'officers'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $location = $this->locations->findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:500'],
            'radius_meters' => ['required', 'integer', 'between:10,500'],
            'minimum_officer' => ['required', 'integer', 'min:1'],
            'padal_id' => ['nullable', 'uuid', 'exists:users,id'],
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
            'padal_id' => $validated['padal_id'] ?? null,
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
