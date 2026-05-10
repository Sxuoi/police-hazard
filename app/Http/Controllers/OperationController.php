<?php

namespace App\Http\Controllers;

use App\Actions\ArchiveOperationAction;
use App\Actions\CreateOperationAction;
use App\Actions\UpdateOperationAction;
use App\Http\Requests\StoreOperationRequest;
use App\Http\Requests\UpdateOperationRequest;
use App\Repositories\Contracts\OperationRepositoryInterface;
use App\Repositories\Contracts\SakerRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function __construct(
        private readonly OperationRepositoryInterface $operations,
        private readonly SakerRepositoryInterface $sakers,
        private readonly CreateOperationAction $createOperation,
        private readonly UpdateOperationAction $updateOperation,
        private readonly ArchiveOperationAction $archiveOperation,
    ) {}

    public function index(Request $request): View
    {
        $operations = $this->operations->paginate(
            perPage: 15,
            filters: $request->only(['status', 'type', 'search']),
        );

        return view('operations.index', compact('operations'));
    }

    public function create(): View
    {
        $sakers = auth()->user()->isGodAdmin()
            ? $this->sakers->getAll()
            : collect([auth()->user()->saker]);

        return view('operations.create', compact('sakers'));
    }

    public function store(StoreOperationRequest $request): RedirectResponse
    {
        $operation = $this->createOperation->execute(
            data: $request->validated(),
            actor: $request->user(),
        );

        return redirect()->route('operations.show', $operation)
            ->with('success', 'Operasi berhasil dibuat.');
    }

    public function show(string $id): View
    {
        $operation = $this->operations->findOrFail($id);
        $operation->loadCount(['zones', 'assignments']);

        return view('operations.show', compact('operation'));
    }

    public function edit(string $id): View
    {
        $operation = $this->operations->findOrFail($id);
        $typeIsLocked = $operation->zones()->exists();
        $sakers = auth()->user()->isGodAdmin()
            ? $this->sakers->getAll()
            : collect([auth()->user()->saker]);

        return view('operations.edit', compact('operation', 'typeIsLocked', 'sakers'));
    }

    public function update(UpdateOperationRequest $request, string $id): RedirectResponse
    {
        $operation = $this->operations->findOrFail($id);

        $this->updateOperation->execute(
            operation: $operation,
            data: $request->validated(),
            actor: $request->user(),
        );

        return redirect()->route('operations.show', $operation)
            ->with('success', 'Operasi berhasil diperbarui.');
    }

    public function archive(Request $request, string $id): RedirectResponse
    {
        $operation = $this->operations->findOrFail($id);

        $this->archiveOperation->execute(
            operation: $operation,
            actor: $request->user(),
        );

        return redirect()->route('operations.index')
            ->with('success', 'Operasi berhasil diarsipkan.');
    }
}
