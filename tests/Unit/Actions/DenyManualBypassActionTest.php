<?php

namespace Tests\Unit\Actions;

use App\Actions\DenyManualBypassAction;
use App\Exceptions\Bypass\BypassExpiredException;
use App\Models\ManualBypassApproval;
use App\Models\User;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DenyManualBypassActionTest extends TestCase
{
    private ManualBypassApprovalRepositoryInterface $bypassRepo;

    private AuditService $auditService;

    private NotificationService $notificationService;

    private DenyManualBypassAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $this->auditService = Mockery::mock(AuditService::class);
        $this->auditService->shouldReceive('log')->byDefault();
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->notificationService->shouldReceive('notifyUser')->byDefault();

        $this->action = new DenyManualBypassAction(
            $this->bypassRepo,
            $this->auditService,
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
        $bypass->expires_at = Carbon::now()->subMinutes(5);

        $this->bypassRepo->shouldReceive('findPendingForUpdate')
            ->with('bypass-uuid-1')
            ->andReturn($bypass);

        $reviewer = new User;
        $reviewer->id = 'reviewer-uuid-1';
        $reviewer->saker_id = 'saker-uuid-1';
        $reviewer->role = 'saker_admin';

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->expectException(BypassExpiredException::class);

        ($this->action)('bypass-uuid-1', 'Reviewer note for the denial.', $reviewer);
    }
}
