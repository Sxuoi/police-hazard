<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateBypassRequestAction;
use App\Exceptions\Bypass\MockLocationNeverBypassableException;
use App\Exceptions\Bypass\OfficerNoteRequiredException;
use App\Exceptions\Bypass\ReasonCodeNotBypassEligibleException;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Support\Dtos\BypassRequestDto;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class CreateBypassRequestActionTest extends TestCase
{
    private AssignmentRepositoryInterface $assignmentRepo;

    private AttendanceRepositoryInterface $attendanceRepo;

    private ManualBypassApprovalRepositoryInterface $bypassRepo;

    private AuditService $auditService;

    private NotificationService $notificationService;

    private CreateBypassRequestAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignmentRepo = Mockery::mock(AssignmentRepositoryInterface::class);
        $this->attendanceRepo = Mockery::mock(AttendanceRepositoryInterface::class);
        $this->bypassRepo = Mockery::mock(ManualBypassApprovalRepositoryInterface::class);
        $this->auditService = Mockery::mock(AuditService::class);
        $this->auditService->shouldReceive('log')->byDefault();
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->notificationService->shouldReceive('notifySakerAdmins')->byDefault();

        $this->action = new CreateBypassRequestAction(
            $this->assignmentRepo,
            $this->attendanceRepo,
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

    private function makeDto(array $overrides = []): BypassRequestDto
    {
        $defaults = [
            'assignmentId' => 'asgn-uuid-1',
            'reasonCode' => 'OUTSIDE_GEOFENCE',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gpsAccuracy' => 10.0,
            'gpsAltitude' => 50.0,
            'gpsSpeed' => 0.0,
            'gpsProvider' => 'gps',
            'timestampDevice' => Carbon::now(),
            'mockLocation' => false,
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'officerNote' => 'Saya berada di lokasi tetapi GPS tidak akurat karena cuaca buruk.',
            'deviceMetadata' => ['os' => 'android'],
        ];

        $merged = array_merge($defaults, $overrides);

        return new BypassRequestDto(...$merged);
    }

    private function makeOfficer(): User
    {
        $officer = new User;
        $officer->id = 'officer-uuid-1';
        $officer->saker_id = 'saker-uuid-1';
        $officer->role = 'officer';

        return $officer;
    }

    public function test_mock_location_throws_never_bypassable(): void
    {
        $dto = $this->makeDto(['mockLocation' => true]);
        $officer = $this->makeOfficer();

        $this->expectException(MockLocationNeverBypassableException::class);

        ($this->action)($dto, $officer);
    }

    public function test_invalid_reason_code_throws(): void
    {
        $dto = $this->makeDto(['reasonCode' => 'MOCK_LOCATION_DETECTED']);
        $officer = $this->makeOfficer();

        $this->expectException(ReasonCodeNotBypassEligibleException::class);

        ($this->action)($dto, $officer);
    }

    public function test_short_officer_note_throws(): void
    {
        $dto = $this->makeDto(['officerNote' => 'Too short']);
        $officer = $this->makeOfficer();

        $this->expectException(OfficerNoteRequiredException::class);

        ($this->action)($dto, $officer);
    }
}
