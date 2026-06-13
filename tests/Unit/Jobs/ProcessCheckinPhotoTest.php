<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessCheckinPhoto;
use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AuditService;
use App\Services\WatermarkService;
use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * @see ProcessCheckinPhoto — Design §7, tasks §8.1–§8.4.
 */
class ProcessCheckinPhotoTest extends TestCase
{
    private string $attendanceId = 'att-uuid-1';

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_happy_path_marks_photo_processed(): void
    {
        config()->set('policehazard.photo.watermark_retry', 3);

        $attendance = $this->fakeAttendance(rawPath: 'checkin-photos/att-uuid-1.jpg');

        $watermark = Mockery::mock(WatermarkService::class);
        $watermark->shouldReceive('watermark')
            ->once()
            ->with(Mockery::on(fn ($a) => $a instanceof Attendance && $a->id === $this->attendanceId))
            ->andReturn('photos/att-uuid-1.jpg');

        $repo = Mockery::mock(AttendanceRepositoryInterface::class);
        $repo->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
        $repo->shouldReceive('markPhotoProcessed')->once()->with($this->attendanceId, 'photos/att-uuid-1.jpg');
        $repo->shouldNotReceive('markPhotoFailed');

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldNotReceive('log');

        $job = new ProcessCheckinPhoto($this->attendanceId);
        $this->bindQueueJob($job, attempts: 1);

        $job->handle($watermark, $repo, $audit);

        $this->assertSame(4, $job->tries, 'tries = 1 + watermark_retry(3)');
    }

    public function test_missing_photo_raw_path_returns_early(): void
    {
        config()->set('policehazard.photo.watermark_retry', 3);

        $attendance = $this->fakeAttendance(rawPath: null);

        $watermark = Mockery::mock(WatermarkService::class);
        $watermark->shouldNotReceive('watermark');

        $repo = Mockery::mock(AttendanceRepositoryInterface::class);
        $repo->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
        $repo->shouldNotReceive('markPhotoProcessed');
        $repo->shouldNotReceive('markPhotoFailed');

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldNotReceive('log');

        $job = new ProcessCheckinPhoto($this->attendanceId);
        $this->bindQueueJob($job, attempts: 1);

        $job->handle($watermark, $repo, $audit);

        $this->assertTrue(true);
    }

    public function test_retry_with_zero_retries_fails_after_one_attempt(): void
    {
        config()->set('policehazard.photo.watermark_retry', 0);

        $attendance = $this->fakeAttendance(rawPath: 'checkin-photos/att-uuid-1.jpg');

        $watermark = Mockery::mock(WatermarkService::class);
        $watermark->shouldReceive('watermark')->once()->andThrow(new RuntimeException('S3 unreachable'));

        $repo = Mockery::mock(AttendanceRepositoryInterface::class);
        $repo->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
        $repo->shouldReceive('markPhotoFailed')->once()->with($this->attendanceId);
        $repo->shouldNotReceive('markPhotoProcessed');

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('PHOTO_WATERMARK_FAILED', Mockery::on(fn ($a) => $a instanceof Attendance && $a->id === $this->attendanceId), Mockery::on(fn ($meta) => ($meta['error'] ?? null) === 'S3 unreachable'));

        $job = new ProcessCheckinPhoto($this->attendanceId);

        $this->assertSame(1, $job->tries, 'watermark_retry=0 yields tries=1');

        // The job's first and only attempt must not re-throw: it marks failed and returns.
        $this->bindQueueJob($job, attempts: 1);
        $job->handle($watermark, $repo, $audit);
    }

    public function test_retry_with_one_retry_fails_after_two_attempts(): void
    {
        config()->set('policehazard.photo.watermark_retry', 1);

        $attendance = $this->fakeAttendance(rawPath: 'checkin-photos/att-uuid-1.jpg');

        $job = new ProcessCheckinPhoto($this->attendanceId);
        $this->assertSame(2, $job->tries);

        // Attempt 1 — must re-throw to let the queue retry.
        $watermark = Mockery::mock(WatermarkService::class);
        $watermark->shouldReceive('watermark')->once()->andThrow(new RuntimeException('transient'));

        $repo = Mockery::mock(AttendanceRepositoryInterface::class);
        $repo->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
        $repo->shouldNotReceive('markPhotoFailed');
        $repo->shouldNotReceive('markPhotoProcessed');

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldNotReceive('log');

        $this->bindQueueJob($job, attempts: 1);

        try {
            $job->handle($watermark, $repo, $audit);
            $this->fail('Expected handle() to re-throw on non-terminal attempt');
        } catch (RuntimeException $e) {
            $this->assertSame('transient', $e->getMessage());
        }

        // Attempt 2 — terminal, must mark failed and audit.
        $watermark2 = Mockery::mock(WatermarkService::class);
        $watermark2->shouldReceive('watermark')->once()->andThrow(new RuntimeException('terminal'));

        $repo2 = Mockery::mock(AttendanceRepositoryInterface::class);
        $repo2->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
        $repo2->shouldReceive('markPhotoFailed')->once()->with($this->attendanceId);
        $repo2->shouldNotReceive('markPhotoProcessed');

        $audit2 = Mockery::mock(AuditService::class);
        $audit2->shouldReceive('log')
            ->once()
            ->with('PHOTO_WATERMARK_FAILED', Mockery::on(fn ($a) => $a instanceof Attendance), Mockery::on(fn ($meta) => ($meta['error'] ?? null) === 'terminal'));

        $this->bindQueueJob($job, attempts: 2);
        $job->handle($watermark2, $repo2, $audit2);
    }

    public function test_retry_with_three_retries_fails_after_four_attempts(): void
    {
        config()->set('policehazard.photo.watermark_retry', 3);

        $attendance = $this->fakeAttendance(rawPath: 'checkin-photos/att-uuid-1.jpg');

        $job = new ProcessCheckinPhoto($this->attendanceId);
        $this->assertSame(4, $job->tries);
        $this->assertSame([10, 30, 90], $job->backoff);

        // Attempts 1–3 — must re-throw, no mark/audit.
        foreach ([1, 2, 3] as $attempt) {
            $watermark = Mockery::mock(WatermarkService::class);
            $watermark->shouldReceive('watermark')->once()->andThrow(new RuntimeException("transient-{$attempt}"));

            $repo = Mockery::mock(AttendanceRepositoryInterface::class);
            $repo->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
            $repo->shouldNotReceive('markPhotoFailed');
            $repo->shouldNotReceive('markPhotoProcessed');

            $audit = Mockery::mock(AuditService::class);
            $audit->shouldNotReceive('log');

            $this->bindQueueJob($job, attempts: $attempt);

            try {
                $job->handle($watermark, $repo, $audit);
                $this->fail("Expected handle() to re-throw on attempt {$attempt}");
            } catch (RuntimeException $e) {
                $this->assertSame("transient-{$attempt}", $e->getMessage());
            }
        }

        // Attempt 4 — terminal.
        $watermark4 = Mockery::mock(WatermarkService::class);
        $watermark4->shouldReceive('watermark')->once()->andThrow(new RuntimeException('final'));

        $repo4 = Mockery::mock(AttendanceRepositoryInterface::class);
        $repo4->shouldReceive('findOrFail')->with($this->attendanceId)->once()->andReturn($attendance);
        $repo4->shouldReceive('markPhotoFailed')->once()->with($this->attendanceId);
        $repo4->shouldNotReceive('markPhotoProcessed');

        $audit4 = Mockery::mock(AuditService::class);
        $audit4->shouldReceive('log')
            ->once()
            ->with('PHOTO_WATERMARK_FAILED', Mockery::on(fn ($a) => $a instanceof Attendance), Mockery::on(fn ($meta) => ($meta['error'] ?? null) === 'final'));

        $this->bindQueueJob($job, attempts: 4);
        $job->handle($watermark4, $repo4, $audit4);
    }

    /**
     * Build an Attendance model instance without touching the database.
     */
    private function fakeAttendance(?string $rawPath): Attendance
    {
        $att = new Attendance;
        $att->id = $this->attendanceId;
        $att->photo_raw_path = $rawPath;

        return $att;
    }

    /**
     * Attach a fake queue job so InteractsWithQueue::attempts() returns our count.
     */
    private function bindQueueJob(ProcessCheckinPhoto $job, int $attempts): void
    {
        $queueJob = Mockery::mock(QueueJobContract::class);
        $queueJob->shouldReceive('attempts')->andReturn($attempts);
        $job->job = $queueJob;
    }
}
