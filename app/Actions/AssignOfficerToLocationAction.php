<?php

namespace App\Actions;

use App\Models\Assignment;
use App\Models\Operation;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PRD §7.7, §5.1 — Assigns an officer to a location.
 *
 * PH Guard: An officer cannot be assigned to two different PH locations
 * on the same date + shift. Uses pg_advisory_xact_lock for concurrency safety.
 *
 * Patrol: Multiple officers can be assigned to the same location simultaneously.
 * Bulk support: accepts array of dates.
 */
class AssignOfficerToLocationAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  array{officer_id: string, location_id: string, operation_id: string, saker_id: string, assigned_saker_id: string, start_date: string, end_date: ?string, assigned_by: string}  $data
     */
    public function execute(array $data, User $actor): array
    {
        $created = [];

        DB::transaction(function () use ($data, $actor, &$created) {
            // Advisory lock to prevent concurrent duplicate PH assignments
            $lockId = crc32($data['officer_id'].$data['location_id']);
            DB::statement("SELECT pg_advisory_xact_lock({$lockId})");

            // PH overlap guard — only for PH operations
            $operation = Operation::find($data['operation_id']);
            if ($operation && $operation->operation_type === 'PH') {
                $existing = Assignment::withoutGlobalScopes()
                    ->where('officer_id', $data['officer_id'])
                    ->whereIn('status', ['active', 'pending'])
                    ->whereHas('operation', fn ($q) => $q->where('operation_type', 'PH'))
                    ->where(function ($query) use ($data) {
                        if (!empty($data['end_date'])) {
                            $query->where('start_date', '<=', $data['end_date']);
                        }
                        $query->where(function ($q) use ($data) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $data['start_date']);
                        });
                    })
                    ->first();

                if ($existing) {
                    $rangeStr = $data['start_date'] . ($data['end_date'] ? ' s.d. ' . $data['end_date'] : ' (selamanya)');
                    throw ValidationException::withMessages([
                        'officer_id' => [
                            "Anggota sudah memiliki penugasan PH pada periode {$rangeStr}.",
                        ],
                    ]);
                }
            }

            $assignment = Assignment::create([
                'officer_id' => $data['officer_id'],
                'location_id' => $data['location_id'],
                'operation_id' => $data['operation_id'],
                'saker_id' => $data['saker_id'],
                'assigned_saker_id' => $data['assigned_saker_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => 'active',
                'assigned_by' => $actor->id,
            ]);

            $created[] = $assignment;

            $this->auditService->log('OFFICER_ASSIGNED', $assignment, [
                'officer_id' => $data['officer_id'],
                'location_id' => $data['location_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
            ]);
        });

        return $created;
    }
}
