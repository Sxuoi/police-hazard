<?php

namespace App\Jobs;

use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AuditService;
use App\Services\WatermarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * ProcessCheckinPhoto — Design §7.
 *
 * Dispatched post-commit from the check-in action. Reads the raw photo from the
 * private disk, applies the server-side watermark, uploads to S3, and transitions
 * `photo_status` from pending to processed. On terminal failure, transitions to
 * failed and audits PHOTO_WATERMARK_FAILED.
 *
 * Retry semantics per R3.14 / R10.6:
 *   tries   = max(1, 1 + config('policehazard.photo.watermark_retry'))
 *   backoff = [10, 30, 90] seconds between retries
 */
class ProcessCheckinPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Total attempts including the first try (honors watermark_retry = 0).
     */
    public int $tries;

    /**
     * Per-retry backoff in seconds.
     *
     * @var array<int,int>
     */
    public array $backoff = [10, 30, 90];

    public function __construct(
        public readonly string $attendanceId,
    ) {
        $retry = (int) config('policehazard.photo.watermark_retry', 3);
        $this->tries = max(1, 1 + $retry);
    }

    public function handle(
        WatermarkService $watermark,
        AttendanceRepositoryInterface $attendanceRepo,
        AuditService $audit,
    ): void {
        $attendance = $attendanceRepo->findOrFail($this->attendanceId);

        // Nothing to process — attendance was stored without a raw photo.
        if (! $attendance->photo_raw_path) {
            return;
        }

        try {
            $s3Key = $watermark->watermark($attendance);
            $attendanceRepo->markPhotoProcessed($attendance->id, $s3Key);
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $attendanceRepo->markPhotoFailed($attendance->id);
                $audit->log('PHOTO_WATERMARK_FAILED', $attendance, [
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            // Re-throw to trigger the queue's retry with the configured backoff.
            throw $e;
        }
    }
}
