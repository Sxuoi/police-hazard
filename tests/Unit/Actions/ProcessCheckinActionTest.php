<?php

namespace Tests\Unit\Actions;

use App\Actions\ProcessCheckinAction;
use App\Exceptions\Checkin\AssignmentNotFoundException;
use App\Exceptions\Checkin\DuplicateCheckinException;
use App\Exceptions\Checkin\MockLocationException;
use App\Exceptions\Checkin\OutsideGeofenceException;
use App\Exceptions\Checkin\OutsideShiftWindowException;
use App\Exceptions\Checkin\PhotoInvalidException;
use App\Exceptions\Checkin\SpoofingRejectedException;
use App\Models\Assignment;
use App\Models\Location;
use App\Models\Operation;
use App\Models\User;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AuditService;
use App\Services\DashboardCacheInvalidator;
use App\Services\GeofenceService;
use App\Services\LocationTimezoneResolver;
use App\Services\SpoofingDetectionService;
use App\Support\Dtos\CheckinDto;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class ProcessCheckinActionTest extends TestCase
{
    private $assignmentRepo;

    private $attendanceRepo;

    private $geofenceService;

    private $spoofingService;

    private $auditService;

    private ProcessCheckinAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignmentRepo = Mockery::mock(AssignmentRepositoryInterface::class);
        $this->attendanceRepo = Mockery::mock(AttendanceRepositoryInterface::class);
        $this->geofenceService = Mockery::mock(GeofenceService::class);
        $this->spoofingService = Mockery::mock(SpoofingDetectionService::class);

        // LocationTimezoneResolver and DashboardCacheInvalidator are final classes.
        // Use real instances — they have no external dependencies for our test paths.
        $timezoneResolver = new LocationTimezoneResolver;
        $cacheInvalidator = new DashboardCacheInvalidator;

        $this->auditService = Mockery::mock(AuditService::class);
        $this->auditService->shouldReceive('log')->byDefault();

        $this->action = new ProcessCheckinAction(
            $this->assignmentRepo,
            $this->attendanceRepo,
            $this->geofenceService,
            $this->spoofingService,
            $timezoneResolver,
            $cacheInvalidator,
            $this->auditService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeDto(array $overrides = []): CheckinDto
    {
        $defaults = [
            'assignmentId' => 'asgn-uuid-1',
            'officerId' => 'officer-uuid-1',
            'locationId' => 'loc-uuid-1',
            'sakerId' => 'saker-uuid-1',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'gpsAccuracy' => 10.0,
            'gpsAltitude' => 50.0,
            'gpsSpeed' => 0.0,
            'gpsProvider' => 'gps',
            'timestampDevice' => Carbon::now(),
            'mockLocation' => false,
            'photo' => UploadedFile::fake()->image('photo.jpg'),
            'checkedInAt' => Carbon::now(),
            'isWithinGeofence' => true,
            'isWithinShift' => true,
            'distanceFromPoint' => 25.0,
            'spoofingScore' => 0,
            'spoofingSignals' => [],
            'deviceMetadata' => ['os' => 'android'],
            'shiftWindowStart' => Carbon::now()->subHours(2),
            'shiftWindowEnd' => Carbon::now()->addHours(2),
            'status' => 'verified',
        ];

        $merged = array_merge($defaults, $overrides);

        return new CheckinDto(...$merged);
    }

    /**
     * Create an assignment mock with shift times that are WITHIN the current time.
     */
    private function makeAssignmentWithinShift(string $operationType = 'PH'): object
    {
        $location = new Location;
        $location->id = 'loc-uuid-1';
        $location->timezone = 'Asia/Jakarta';

        // Set operation to cover the current hour in WIB (UTC+7)
        $nowWib = Carbon::now('Asia/Jakarta');
        $startTime = $nowWib->copy()->subHours(2)->format('H:i');
        $endTime = $nowWib->copy()->addHours(2)->format('H:i');

        $operation = new Operation;
        $operation->operation_type = $operationType;
        $operation->start_time = $startTime;
        $operation->end_time = $endTime;

        $officer = new User;
        $officer->id = 'officer-uuid-1';

        $assignment = Mockery::mock(Assignment::class)->makePartial();
        $assignment->shouldReceive('getAttribute')->with('id')->andReturn('asgn-uuid-1');
        $assignment->shouldReceive('getAttribute')->with('start_date')->andReturn(Carbon::today('Asia/Jakarta'));
        $assignment->shouldReceive('getAttribute')->with('end_date')->andReturn(null);
        $assignment->shouldReceive('loadMissing')->andReturnSelf();
        $assignment->shouldReceive('getAttribute')->with('location')->andReturn($location);
        $assignment->shouldReceive('getAttribute')->with('operation')->andReturn($operation);
        $assignment->shouldReceive('getAttribute')->with('officer')->andReturn($officer);

        return $assignment;
    }

    /**
     * Create an assignment mock with operation times that are OUTSIDE the current time.
     */
    private function makeAssignmentOutsideShift(string $operationType = 'PH'): object
    {
        $location = new Location;
        $location->id = 'loc-uuid-1';
        $location->timezone = 'Asia/Jakarta';

        // Use yesterday's date and a narrow past time window
        $operation = new Operation;
        $operation->operation_type = $operationType;
        $operation->start_time = '01:00';
        $operation->end_time = '02:00';

        $officer = new User;
        $officer->id = 'officer-uuid-1';

        $assignment = Mockery::mock(Assignment::class)->makePartial();
        $assignment->shouldReceive('getAttribute')->with('id')->andReturn('asgn-uuid-1');
        // Use yesterday so the 01:00-02:00 window is definitely past
        $assignment->shouldReceive('getAttribute')->with('start_date')->andReturn(Carbon::yesterday('Asia/Jakarta'));
        $assignment->shouldReceive('getAttribute')->with('end_date')->andReturn(null);
        $assignment->shouldReceive('loadMissing')->andReturnSelf();
        $assignment->shouldReceive('getAttribute')->with('location')->andReturn($location);
        $assignment->shouldReceive('getAttribute')->with('operation')->andReturn($operation);
        $assignment->shouldReceive('getAttribute')->with('officer')->andReturn($officer);

        return $assignment;
    }

    public function test_assignment_not_found_throws(): void
    {
        $dto = $this->makeDto();

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->with($dto->officerId, $dto->sakerId)
            ->andReturn(null);

        $this->expectException(AssignmentNotFoundException::class);

        ($this->action)($dto);
    }

    public function test_outside_shift_window_throws(): void
    {
        $dto = $this->makeDto();
        $assignment = $this->makeAssignmentOutsideShift();

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->expectException(OutsideShiftWindowException::class);

        ($this->action)($dto);
    }

    public function test_mock_location_throws(): void
    {
        $dto = $this->makeDto(['mockLocation' => true]);
        $assignment = $this->makeAssignmentWithinShift();

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->expectException(MockLocationException::class);

        ($this->action)($dto);
    }

    public function test_outside_geofence_throws(): void
    {
        $dto = $this->makeDto();
        $assignment = $this->makeAssignmentWithinShift();

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->geofenceService->shouldReceive('distanceFromLocation')
            ->andReturn(150.0);
        $this->geofenceService->shouldReceive('isWithinGeofence')
            ->andReturn(false);

        $this->expectException(OutsideGeofenceException::class);

        ($this->action)($dto);
    }

    public function test_spoofing_rejected_throws(): void
    {
        $dto = $this->makeDto();
        $assignment = $this->makeAssignmentWithinShift();

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->geofenceService->shouldReceive('distanceFromLocation')->andReturn(10.0);
        $this->geofenceService->shouldReceive('isWithinGeofence')->andReturn(true);

        // Return a high spoofing score that exceeds auto_reject_score (default 2)
        $this->spoofingService->shouldReceive('score')
            ->andReturn((object) [
                'score' => 3,
                'signals' => [['signal' => 'MOCK_LOCATION', 'value' => true]],
            ]);

        $this->expectException(SpoofingRejectedException::class);

        ($this->action)($dto);
    }

    public function test_duplicate_checkin_throws_for_ph(): void
    {
        $dto = $this->makeDto();
        $assignment = $this->makeAssignmentWithinShift('PH');

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->geofenceService->shouldReceive('distanceFromLocation')->andReturn(10.0);
        $this->geofenceService->shouldReceive('isWithinGeofence')->andReturn(true);

        $this->spoofingService->shouldReceive('score')
            ->andReturn((object) ['score' => 0, 'signals' => []]);

        // Already has a verified attendance
        $this->attendanceRepo->shouldReceive('verifiedExistsFor')
            ->with('asgn-uuid-1')
            ->andReturn(true);

        $this->expectException(DuplicateCheckinException::class);

        ($this->action)($dto);
    }

    public function test_photo_invalid_throws(): void
    {
        // Create a file with invalid magic bytes (not JPEG or PNG)
        $dto = $this->makeDto([
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);
        $assignment = $this->makeAssignmentWithinShift('PATROL');

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->geofenceService->shouldReceive('distanceFromLocation')->andReturn(10.0);
        $this->geofenceService->shouldReceive('isWithinGeofence')->andReturn(true);

        $this->spoofingService->shouldReceive('score')
            ->andReturn((object) ['score' => 0, 'signals' => []]);

        $this->expectException(PhotoInvalidException::class);

        ($this->action)($dto);
    }

    public function test_midnight_spanning_shift_checkin_success(): void
    {
        // Fake the queue so background jobs are not executed during this test
        \Illuminate\Support\Facades\Queue::fake();

        // Mock time to early morning (01:00) Jan 16, 2026
        Carbon::setTestNow(Carbon::parse('2026-01-16 01:00:00', 'Asia/Jakarta'));

        $location = new Location;
        $location->id = 'loc-uuid-1';
        $location->timezone = 'Asia/Jakarta';

        // Midnight-spanning shift starting Jan 15 at 22:00 and ending Jan 16 at 06:00
        $operation = new Operation;
        $operation->operation_type = 'PATROL';
        $operation->start_time = '22:00';
        $operation->end_time = '06:00';

        $officer = new User;
        $officer->id = 'officer-uuid-1';

        $assignment = Mockery::mock(Assignment::class)->makePartial();
        $assignment->shouldReceive('getAttribute')->with('id')->andReturn('asgn-uuid-1');
        // Assignment started Jan 15
        $assignment->shouldReceive('getAttribute')->with('start_date')->andReturn(Carbon::parse('2026-01-15', 'Asia/Jakarta'));
        $assignment->shouldReceive('getAttribute')->with('end_date')->andReturn(null);
        $assignment->shouldReceive('loadMissing')->andReturnSelf();
        $assignment->shouldReceive('getAttribute')->with('location')->andReturn($location);
        $assignment->shouldReceive('getAttribute')->with('operation')->andReturn($operation);
        $assignment->shouldReceive('getAttribute')->with('officer')->andReturn($officer);

        $dto = $this->makeDto([
            'timestampDevice' => Carbon::now(),
            'checkedInAt' => Carbon::now(),
        ]);

        $this->assignmentRepo->shouldReceive('findForOfficerToday')
            ->andReturn($assignment);

        $this->geofenceService->shouldReceive('distanceFromLocation')->andReturn(10.0);
        $this->geofenceService->shouldReceive('isWithinGeofence')->andReturn(true);

        $this->spoofingService->shouldReceive('score')
            ->andReturn((object) ['score' => 0, 'signals' => []]);

        $mockAttendance = new \App\Models\Attendance();
        $mockAttendance->id = '019eed70-9be0-7cb5-a10c-f3769c024501';

        // Mock photo stripping and saving path
        $this->attendanceRepo->shouldReceive('insertVerified')
            ->once()
            ->andReturn($mockAttendance);

        // Call the action, should not throw OutsideShiftWindowException
        ($this->action)($dto);

        // Assert the job was pushed to queue
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessCheckinPhoto::class);

        // Reset test time
        Carbon::setTestNow();
        $this->assertTrue(true);
    }
}
