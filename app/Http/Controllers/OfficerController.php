<?php

namespace App\Http\Controllers;

use App\Actions\RevokeOfficerTokensAction;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OfficerController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuditService $auditService,
        private readonly RevokeOfficerTokensAction $revokeTokensAction,
    ) {}

    public function index(Request $request): View
    {
        // PRD §7.6: default sort by tardiness (longest delay at top)
        $officers = $this->users->paginateOfficers(
            perPage: 20,
            filters: $request->only(['search', 'saker_id', 'status']),
            date: $request->get('date', now()->toDateString()),
        );

        return view('officers.index', compact('officers'));
    }

    public function create(): View
    {
        return view('officers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nrp' => ['required', 'string', 'max:20', 'unique:users,nrp'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'safung' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $officer = $this->users->create(array_merge($validated, [
            'saker_id' => auth()->user()->saker_id,
            'role' => 'officer',
            'is_active' => true,
            'password' => Hash::make($validated['password']),
        ]));

        $this->auditService->log('OFFICER_CREATED', $officer, ['nrp' => $officer->nrp]);

        return redirect()->route('officers.show', $officer)
            ->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function show(string $id): View
    {
        $officer = $this->users->findOrFail($id);
        $officer->load('saker');

        return view('officers.show', compact('officer'));
    }

    public function edit(string $id): View
    {
        $officer = $this->users->findOrFail($id);

        return view('officers.edit', compact('officer'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $officer = $this->users->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150', "unique:users,email,{$officer->id}"],
            'safung' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $wasActive = $officer->is_active;

        $officer->update($validated);

        // Revoke all Sanctum tokens when officer is deactivated (R1.14)
        if ($wasActive && ! $officer->is_active) {
            ($this->revokeTokensAction)($officer);
        }

        $this->auditService->log('OFFICER_UPDATED', $officer, ['changes' => $officer->getChanges()]);

        return redirect()->route('officers.show', $officer)
            ->with('success', 'Data anggota berhasil diperbarui.');
    }
}
