<?php

namespace App\Http\Controllers;

use App\Actions\ApproveManualBypassAction;
use App\Actions\DenyManualBypassAction;
use App\Exceptions\Bypass\BypassExpiredException;
use App\Exceptions\Bypass\MockLocationNeverBypassableException;
use App\Http\Requests\Admin\ApproveBypassRequest;
use App\Http\Requests\Admin\DenyBypassRequest;
use App\Models\ManualBypassApproval;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BypassApprovalController extends Controller
{
    public function __construct(
        private readonly ManualBypassApprovalRepositoryInterface $bypassRepo,
        private readonly ApproveManualBypassAction $approveAction,
        private readonly DenyManualBypassAction $denyAction,
    ) {}

    /**
     * Paginated list of bypass requests for the supervisor's scope.
     * God Admin sees all; Saker Admin sees only their saker.
     * Default filter: status=pending.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // God Admin → null (no saker filter).
        // Saker Admin → entire hierarchy under them. POLDA admin gets POLDA
        // + every POLRESTABES + every POLSEK; POLSEK admin gets just POLSEK.
        $sakerScope = $user->isGodAdmin() ? null : $user->accessibleSakerIds();

        $filters = [
            'status' => $request->query('status', 'pending'),
            'bypass_reason' => $request->query('bypass_reason'),
            'officer_id' => $request->query('officer_id'),
        ];

        $page = (int) $request->query('page', 1);

        $bypasses = $this->bypassRepo->listForSupervisor($sakerScope, $filters, $page);

        return view('bypass-approvals.index', compact('bypasses', 'filters'));
    }

    /**
     * Detail view with officer info, assignment details, comparison map data, photo.
     */
    public function show(string $id): View
    {
        $bypass = ManualBypassApproval::withoutGlobalScopes()
            ->with(['officer:id,name,nrp,saker_id', 'assignment.location', 'assignment.shift', 'reviewer:id,name'])
            ->findOrFail($id);

        return view('bypass-approvals.show', compact('bypass'));
    }

    /**
     * Approve a pending bypass request.
     */
    public function approve(ApproveBypassRequest $request, string $id): RedirectResponse
    {
        try {
            ($this->approveAction)($id, $request->validated()['reviewer_note'], $request->user());

            return redirect()->route('bypass-approvals.index')
                ->with('success', 'Bypass berhasil disetujui. Kehadiran telah dicatat.');
        } catch (MockLocationNeverBypassableException $e) {
            return redirect()->route('bypass-approvals.show', $id)
                ->with('error', 'Mock location tidak dapat di-bypass.');
        } catch (BypassExpiredException $e) {
            return redirect()->route('bypass-approvals.show', $id)
                ->with('error', 'Bypass telah kedaluwarsa.');
        } catch (AccessDeniedHttpException $e) {
            return redirect()->route('bypass-approvals.index')
                ->with('error', 'Anda tidak memiliki akses untuk menyetujui bypass ini.');
        }
    }

    /**
     * Deny a pending bypass request.
     */
    public function deny(DenyBypassRequest $request, string $id): RedirectResponse
    {
        try {
            ($this->denyAction)($id, $request->validated()['reviewer_note'], $request->user());

            return redirect()->route('bypass-approvals.index')
                ->with('success', 'Bypass berhasil ditolak.');
        } catch (BypassExpiredException $e) {
            return redirect()->route('bypass-approvals.show', $id)
                ->with('error', 'Bypass telah kedaluwarsa.');
        } catch (AccessDeniedHttpException $e) {
            return redirect()->route('bypass-approvals.index')
                ->with('error', 'Anda tidak memiliki akses untuk menolak bypass ini.');
        }
    }
}
