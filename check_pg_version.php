<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$v = DB::select('SELECT version()');
echo $v[0]->version."\n";

// Test if a direct UPDATE with all columns specified works
echo "\nTesting direct UPDATE on manual_bypass_approvals...\n";
try {
    $sakerId = Uuid::uuid7()->toString();
    $userId = Uuid::uuid7()->toString();
    $opId = Uuid::uuid7()->toString();
    $zoneId = Uuid::uuid7()->toString();
    $locId = Uuid::uuid7()->toString();
    $shiftId = Uuid::uuid7()->toString();
    $asgId = Uuid::uuid7()->toString();
    $bypassId = Uuid::uuid7()->toString();

    // Insert minimal records
    DB::table('sakers')->insert(['id' => $sakerId, 'name' => 'Test', 'code' => 'TST-'.substr($sakerId, 0, 8), 'type' => 'POLDA', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('users')->insert(['id' => $userId, 'saker_id' => $sakerId, 'name' => 'Test', 'nrp' => 'NRP'.substr($sakerId, 0, 10), 'role' => 'officer', 'password' => bcrypt('x'), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('operations')->insert(['id' => $opId, 'saker_id' => $sakerId, 'name' => 'Op', 'operation_type' => 'PH', 'status' => 'active', 'start_time' => '08:00:00', 'created_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('zones')->insert(['id' => $zoneId, 'operation_id' => $opId, 'saker_id' => $sakerId, 'name' => 'Z', 'is_active' => true, 'created_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    DB::statement("INSERT INTO locations (id,zone_id,saker_id,name,coordinates,created_by,created_at,updated_at) VALUES ('{$locId}','{$zoneId}','{$sakerId}','L',ST_SetSRID(ST_MakePoint(106.8,−6.2),4326),'{$userId}',NOW(),NOW())");
    DB::statement("INSERT INTO shifts (id,location_id,name,shift_start,shift_end,active_days,is_active,created_at,updated_at) VALUES ('{$shiftId}','{$locId}','S','08:00:00','16:00:00',ARRAY[1,2,3,4,5]::SMALLINT[],true,NOW(),NOW())");
    DB::table('assignments')->insert(['id' => $asgId, 'officer_id' => $userId, 'location_id' => $locId, 'shift_id' => $shiftId, 'operation_id' => $opId, 'saker_id' => $sakerId, 'assigned_saker_id' => $sakerId, 'assignment_date' => now()->toDateString(), 'status' => 'active', 'assigned_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('manual_bypass_approvals')->insert(['id' => $bypassId, 'assignment_id' => $asgId, 'officer_id' => $userId, 'saker_id' => $sakerId, 'bypass_reason' => 'OUTSIDE_GEOFENCE', 'officer_note' => 'Test note', 'status' => 'pending', 'expires_at' => now()->addMinutes(15), 'created_at' => now()]);

    // Try the update
    $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();
    echo "Before: status={$row->status}\n";

    DB::table('manual_bypass_approvals')->where('id', $bypassId)->update(['status' => 'approved', 'reviewed_by' => $userId, 'reviewer_note' => 'OK', 'reviewed_at' => now()]);
    $row = DB::table('manual_bypass_approvals')->where('id', $bypassId)->first();
    echo "After: status={$row->status}\n";
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}
