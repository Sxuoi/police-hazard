<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function index(Request $request): View
    {
        $logs = $this->auditLogs->paginate(
            perPage: 25,
            filters: $request->only(['event_type', 'actor_id', 'entity_type', 'date_from', 'date_to']),
        );

        return view('audit-logs.index', compact('logs'));
    }
}
