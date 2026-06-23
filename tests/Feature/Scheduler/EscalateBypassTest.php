<?php

namespace Tests\Feature\Scheduler;

use App\Actions\EscalateBypassRequestsAction;
use App\Models\ManualBypassApproval;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EscalateBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_requests_escalate_from_level_0_to_1(): void
    {
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = 'bypass-1';
        $bypass->saker_id = 'saker-1';
        $bypass->escalation_level = 0;

        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(0, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([$bypass]));
        $bypassRepo->shouldReceive('advanceEscalation')
            ->once()
            ->with($bypass, 1);
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(1, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifySakerAdmins')
            ->once()
            ->with(
                'saker-1',
                'bypass_escalated',
                Mockery::type('string'),
                Mockery::type('string'),
                null,
                Mockery::type('array'),
            );

        $action = new EscalateBypassRequestsAction($bypassRepo, $notificationService);

        // Travel past the god_admin_after_minutes threshold
        $this->travel(6)->minutes();

        $action();

        $this->assertTrue(true);
    }

    public function test_pending_requests_escalate_from_level_1_to_2(): void
    {
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = 'bypass-2';
        $bypass->saker_id = 'saker-1';
        $bypass->escalation_level = 1;

        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(0, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(1, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([$bypass]));
        $bypassRepo->shouldReceive('advanceEscalation')
            ->once()
            ->with($bypass, 2);

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifySakerAdmins')
            ->once()
            ->with(
                'saker-1',
                'bypass_escalated_email',
                Mockery::type('string'),
                Mockery::type('string'),
                null,
                Mockery::type('array'),
            );

        $action = new EscalateBypassRequestsAction($bypassRepo, $notificationService);

        // Travel past the email_after_minutes threshold
        $this->travel(11)->minutes();

        $action();

        $this->assertTrue(true);
    }

    public function test_no_pending_requests_does_nothing(): void
    {
        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(0, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(1, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo->shouldNotReceive('advanceEscalation');

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldNotReceive('notifySakerAdmins');

        $action = new EscalateBypassRequestsAction($bypassRepo, $notificationService);

        $action();

        $this->assertTrue(true);
    }

    public function test_escalation_is_idempotent_after_level_advance(): void
    {
        // Simulate: level 0 requests already advanced (empty), level 1 has one
        $bypass = Mockery::mock(ManualBypassApproval::class)->makePartial();
        $bypass->id = 'bypass-3';
        $bypass->saker_id = 'saker-2';
        $bypass->escalation_level = 1;

        $bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(0, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo->shouldReceive('listPendingAtEscalationLevel')
            ->with(1, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([$bypass]));
        $bypassRepo->shouldReceive('advanceEscalation')
            ->once()
            ->with($bypass, 2);

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('notifySakerAdmins')->once();

        $action = new EscalateBypassRequestsAction($bypassRepo, $notificationService);

        $this->travel(15)->minutes();

        // Run twice — second run should find nothing at level 1 since it was advanced
        $action();

        // Second invocation: repo returns empty for both levels
        $bypassRepo2 = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $bypassRepo2->shouldReceive('listPendingAtEscalationLevel')
            ->with(0, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo2->shouldReceive('listPendingAtEscalationLevel')
            ->with(1, Mockery::type('int'))
            ->once()
            ->andReturn(new Collection([]));
        $bypassRepo2->shouldNotReceive('advanceEscalation');

        $notificationService2 = Mockery::mock(NotificationService::class);
        $notificationService2->shouldNotReceive('notifySakerAdmins');

        $action2 = new EscalateBypassRequestsAction($bypassRepo2, $notificationService2);
        $action2();

        $this->assertTrue(true);
    }
}
