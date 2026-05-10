<?php

namespace App\Actions;

use App\Models\Assignment;
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
     * @param  array{officer_id: string, location_id: string, shift_id: string, operation_id: string, saker_id: string, assigned_saker_id: string, dates: string[], assigned_by: string}  $data
     */
    public function execute(array $data, User $actor): array
    {
        $created = [];

        DB::transaction(function () use ($data, $actor, &$created) {
            // Advisory lock to prevent concurrent duplicate PH assignments
            $lockId = crc32($data['officer_id'] . $data['shift_id']);
            DB::statement("SELECT pg_advisory_xact_lock({$lockId})");

            foreach ($data['dates'] as $date) {
                // PH overlap guard — only for PH operations
                $operation = \App\Models\Operation::find($data['operation_id']);
                if ($operation && $operation->operation_type === 'PH') {
                    $existing = Assignment::withoutGlobalScopes()
                        ->where('officer_id', $data['officer_id'])
                        ->where('shift_id', $data['shift_id'])
                        ->where('assignment_date', $date)
                        ->whereIn('status', ['active', 'pending'])
                        ->whereHas('operation', fn ($q) => $q->where('operation_type', 'PH'))
                        ->first();

                    if ($existing) {
                        throw ValidationException::withMessages([
                            'officer_id' => [
                                "Anggota sudah memiliki penugasan PH pada tanggal {$date} untuk shift yang sama."
                            ],
                        ]);
                    }
                }

                $assignment = Assignment::create([
                    'officer_id'        => $data['officer_id'],
                    'location_id'       => $data['location_id'],
                    'shift_id'          => $data['shift_id'],
                    'operation_id'      => $data['operation_id'],
                    'saker_id'          => $data['saker_id'],
                    'assigned_saker_id' => $data['assigned_saker_id'],
                    'assignment_date'   => $date,
                    'status'            => 'active',
                    'assigned_by'       => $actor->id,
                ]);

                $created[] = $assignment;

                $this->auditService->log('OFFICER_ASSIGNED', $assignment, [
                    'officer_id'  => $data['officer_id'],
                    'location_id' => $data['location_id'],
                    'date'        => $date,
                ]);
            }
        });

        return $created;
    }
}
