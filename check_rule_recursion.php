<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

echo 'PostgreSQL version: ';
$v = DB::select('SELECT version()');
echo $v[0]->version."\n\n";

// Check if the rules cause infinite recursion by examining the rule definitions
// The issue: DO INSTEAD UPDATE on same table re-triggers the rule
// PostgreSQL should handle this with rule recursion detection, but it errors out

// Let's try using a raw UPDATE with all columns to see if the rule fires correctly
// The narrow_transition rule requires ALL immutable columns to match OLD values
// When we do: UPDATE SET status='approved', reviewed_by=X, reviewer_note=Y, reviewed_at=Z
// The rule sees: NEW.bypass_reason = OLD.bypass_reason (true), NEW.officer_note = OLD.officer_note (true), etc.
// So the rule fires and does: UPDATE SET status=NEW.status, reviewed_by=NEW.reviewed_by, ...
// That inner UPDATE also triggers the rule again with the same conditions -> infinite recursion

// The fix: the rule's inner UPDATE should use a WHERE that won't re-trigger
// OR the rule should be written differently

// Let's check if disabling the rule temporarily works
echo "Testing with rule disabled temporarily...\n";
try {
    DB::statement('ALTER TABLE manual_bypass_approvals DISABLE RULE narrow_transition_manual_bypass');
    DB::statement('ALTER TABLE manual_bypass_approvals DISABLE RULE reject_other_update_manual_bypass');

    $sakerId = Uuid::uuid7()->toString();
    $userId = Uuid::uuid7()->toString();
    $opId = Uuid::uuid7()->toString();
    $zoneId = Uuid::uuid7()->toString();
    $locId = Uuid::uuid7()->toString();
    $shiftId = Uuid::uuid7()->toString();
    $asgId = Uuid::uuid7()->toString();
    $bypassId = Uuid::uuid7()->toString();

    DB::table('sakers')->insert(['id' => $sakerId, 'name' => 'Test', 'code' => 'TST-'.substr($sakerId, 0, 8), 'type' => 'POLDA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('users')->insert(['id' => $userId, 'saker_id' => $sakerId, 'name' => 'Test', 'nrp' => 'NRP'.substr($sakerId, 0, 10), 'role' => 'officer', 'password' => bcrypt('x'), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('operations')->insert(['id' => $opId, 'saker_id' => $sakerId, 'name' => 'Op', 'operation_type' => 'PH', 'status' => 'active', 'start_time' => '08:00:00', 'created_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('zones')->insert(['id' => $zoneId, 'operation_id' => $opId, 'saker_id' => $sakerId, 'name' => 'Z', 'is_active' => true, 'created_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    DB::statement("INSERT INTO locations (id,zone_id,saker_id,name,coordinates,created_by,created_at,updated_at) VALUES ('{$locId}','{$zoneId}','{$sakerId}','L',ST_SetSRID(ST_MakePoint(106.8456,-6.2088),4326),'{$userId}',NOW(),NOW())");
    DB::statement("INSERT INTO shifts (id,location_id,name,shift_start,shift_end,active_days,is_active,created_at,updated_at) VALUES ('{$shiftId}','{$locId}','S','08:00:00','16:00:00',ARRAY[1,2,3,4,5]::SMALLINT[],true,NOW(),NOW())");
    DB::table('assignments')->insert(['id' => $asgId, 'officer_id' => $userId, 'location_id' => $locId, 'shift_id' => $shiftId, 'operation_id' => $opId, 'saker_id' => $sakerId, 'assigned_saker_id' => $sakerId, 'assignment_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('manual_bypass_approvals')->insert(['id' => $bypassId, 'assignment_id' => $asgId, 'officer_id' => $userId, 'saker_id' => $sakerId, 'bypass_reason' => 'OUTSIDE_GEOFENCE', 'officer_note' => 'Test note', 'status' => 'pending', 'expires_at' => now()->addMinutes(15), 'created_at' => now()]);

    DB::statement('ALTER TABLE manual_bypass_approvals ENABLE RULE narrow_transition_manual_bypass');
    DB::statement('ALTER TABLE manual_bypass_approvals ENABLE RULE reject_other_update_manual_bypass');

    $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();
    echo "Before: status={$row->status}\n";

    DB::table('manual_bypass_approvals')->where('id', $bypassId)->update(['status' => 'approved', 'reviewed_by' => $userId, 'reviewer_note' => 'OK', 'reviewed_at' => now()]);
    $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();
    echo "After: status={$row->status}\n";
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}
