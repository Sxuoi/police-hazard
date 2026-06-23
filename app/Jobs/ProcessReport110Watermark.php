<?php

namespace App\Jobs;

use App\Services\WatermarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ProcessReport110Watermark — Applies watermark to Report 110 photos in the background.
 *
 * Dispatched after the Pamapta form saves the raw photo. Reads the stored photo,
 * applies the server-side watermark overlay (coordinates, timestamp, officer info),
 * and overwrites the file in-place.
 *
 * This keeps the HTTP response instant for field officers on slow mobile connections.
 */
class ProcessReport110Watermark implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly string $photoPath,
        public readonly array $watermarkData,
    ) {}

    public function handle(WatermarkService $watermark): void
    {
        $fullPath = storage_path('app/public/' . $this->photoPath);

        if (! file_exists($fullPath)) {
            Log::warning("ProcessReport110Watermark: Photo not found at {$fullPath}");
            return;
        }

        $watermark->applyWatermark($fullPath, $this->watermarkData);
    }

    public function failed(Throwable $e): void
    {
        Log::error("ProcessReport110Watermark failed for {$this->photoPath}: {$e->getMessage()}");
    }
}
