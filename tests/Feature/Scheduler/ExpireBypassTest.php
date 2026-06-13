<?php

namespace Tests\Feature\Scheduler;

use App\Actions\ExpireBypassRequestsAction;
use App\Models\ManualBypassApproval;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ExpireBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_bypass_requests_past_ttl_are_expired(): void
    {
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = 'bypass-1';
        $bypass->officer_id = 'officer-1';
        $bypass->saker_id = 'saker-1';
        $bypass->bypass_reason = 'OUTSIDE_GEOFENCE';
        $bypass->status = 'pending';

        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listExpirable')
            ->once()
            ->andReturn(new Collection([$bypass]));
        $bypassRepo->shouldReceive('markExpired')
            ->once()
            ->with($bypass);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->once()
            ->with('MANUAL_BYPASS_EXPIRED', $bypass, Mockery::type('array'));

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyUser')
            ->once();

        $action = new ExpireBypassRequestsAction($bypassRepo, $auditService, $notificationService);

        // Travel past the TTL
        $this->travel(20)->minutes();

        $action();

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function test_no_expirable_requests_does_nothing(): void
    {
        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listExpirable')
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo->shouldNotReceive('markExpired');

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldNotReceive('log');

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldNotReceive('notifyUser');

        $action = new ExpireBypassRequestsAction($bypassRepo, $auditService, $notificationService);

        $action();

        $this->assertTrue(true);
    }

    public function test_multiple_expired_requests_are_all_transitioned(): void
    {
        $bypasses = new Collection([
            $this->makeMockBypass('bypass-1', 'officer-1'),
            $this->makeMockBypass('bypass-2', 'officer-2'),
            $this->makeMockBypass('bypass-3', 'officer-3'),
        ]);

        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listExpirable')
            ->once()
            ->andReturn($bypasses);
        $bypassRepo->shouldReceive('markExpired')->times(3);

        $auditService = Mockery::mock(AuditService::class);
        $auditService->shouldReceive('log')
            ->times(3)
            ->with('MANUAL_BYPASS_EXPIRED', Mockery::type(ManualBypassApproval::class), Mockery::type('array'));

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifyUser')->times(3);

        $action = new ExpireBypassRequestsAction($bypassRepo, $auditService, $notificationService);

        $this->travel(30)->minutes();

        $action();

        $this->assertTrue(true);
    }

    private function makeMockBypass(string $id, string $officerId): ManualBypassApproval
    {
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = $id;
        $bypass->officer_id = $officerId;
        $bypass->saker_id = 'saker-1';
        $bypass->bypass_reason = 'OUTSIDE_GEOFENCE';
        $bypass->status = 'pending';

        return $bypass;
    }
}
