<?php

namespace Tests\Unit\Actions;

use App\Actions\ApproveManualBypassAction;
use App\Exceptions\Bypass\BypassExpiredException;
use App\Models\ManualBypassApproval;
use App\Models\User;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\DashboardCacheInvalidator;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class ApproveManualBypassActionTest extends TestCase
{
    private $bypassRepo;

    private $attendanceRepo;

    private $auditService;

    private $cacheInvalidator;

    private $notificationService;

    private ApproveManualBypassAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $this->attendanceRepo = Mockery::mock(AttendanceRepositoryInterface::class);
        $this->auditService = Mockery::mock(AuditService::class);
        $this->auditService->shouldReceive('log')->byDefault();

        // DashboardCacheInvalidator is final — use real instance (not reached in exception tests)
        $this->cacheInvalidator = new DashboardCacheInvalidator;

        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->notificationService->shouldReceive('notifyUser')->byDefault();

        $this->action = new ApproveManualBypassAction(
            $this->bypassRepo,
            $this->attendanceRepo,
            $this->auditService,
            $this->cacheInvalidator,
            $this->notificationService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_expired_bypass_throws(): void
    {
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = 'bypass-uuid-1';
        $bypass->saker_id = 'saker-uuid-1';
        $bypass->bypass_reason = 'OUTSIDE_GEOFENCE';
        $bypass->expires_at = Carbon::now()->subMinutes(5);
        $bypass->shouldReceive('loadMissing')->andReturnSelf();

        $this->bypassRepo->shouldReceive('findPendingForUpdate')
            ->with('bypass-uuid-1')
            ->andReturn($bypass);

        $reviewer = new User;
        $reviewer->id = 'reviewer-uuid-1';
        $reviewer->saker_id = 'saker-uuid-1';
        $reviewer->role = 'saker_admin';

        // DB::transaction should execute the callback
        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->expectException(BypassExpiredException::class);

        ($this->action)('bypass-uuid-1', 'Reviewer note for the bypass decision.', $reviewer);
    }

    public function test_cross_tenant_throws(): void
    {
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = 'bypass-uuid-2';
        $bypass->saker_id = 'saker-uuid-1';
        $bypass->bypass_reason = 'OUTSIDE_GEOFENCE';
        $bypass->expires_at = Carbon::now()->addMinutes(10);
        $bypass->shouldReceive('loadMissing')->andReturnSelf();

        $this->bypassRepo->shouldReceive('findPendingForUpdate')
            ->with('bypass-uuid-2')
            ->andReturn($bypass);

        // Reviewer from a different saker and NOT a god admin
        $reviewer = new User;
        $reviewer->id = 'reviewer-uuid-2';
        $reviewer->saker_id = 'saker-uuid-999';
        $reviewer->role = 'saker_admin';

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->expectException(AccessDeniedHttpException::class);

        ($this->action)('bypass-uuid-2', 'Reviewer note for the bypass decision.', $reviewer);
    }
}
