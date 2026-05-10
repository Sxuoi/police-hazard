<?php

namespace App\Http\Controllers;

use App\Actions\CreateZoneAction;
use App\Repositories\Contracts\OperationRepositoryInterface;
use App\Repositories\Contracts\ZoneRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ZoneController extends Controller
{
    public function __construct(
        private readonly ZoneRepositoryInterface $zones,
        private readonly OperationRepositoryInterface $operations,
        private readonly CreateZoneAction $createZone,
    ) {}

    public function index(Request $request): View
    {
        $zones = $this->zones->paginate(
            perPage: 15,
            filters: $request->only(['operation_id', 'search']),
        );

        return view('zones.index', compact('zones'));
    }

    public function create(Request $request): View
    {
        $operations = $this->operations->allActive();

        return view('zones.create', compact('operations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'operation_id' => ['required', 'uuid', 'exists:operations,id'],
            'name'         => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        $zone = $this->createZone->execute($validated, $request->user());

        return redirect()->route('zones.show', $zone)
            ->with('success', 'Zona berhasil dibuat.');
    }

    public function show(string $id): View
    {
        $zone = $this->zones->findOrFail($id);
        $zone->loadCount('locations');

        return view('zones.show', compact('zone'));
    }

    public function edit(string $id): View
    {
        $zone = $this->zones->findOrFail($id);
        $operations = $this->operations->allActive();

        return view('zones.edit', compact('zone', 'operations'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $zone = $this->zones->findOrFail($id);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        $zone->update(array_merge($validated, ['updated_by' => $request->user()->id]));

        return redirect()->route('zones.show', $zone)
            ->with('success', 'Zona berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $zone = $this->zones->findOrFail($id);

        // PRD §7.4: blocked if zone has active locations
        if ($zone->locations()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'zone' => ['Zona tidak dapat dihapus karena masih memiliki lokasi aktif.'],
            ]);
        }

        $zone->delete();

        return redirect()->route('zones.index')
            ->with('success', 'Zona berhasil dihapus.');
    }
}
