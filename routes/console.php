<?php

use App\Actions\EscalateBypassRequestsAction;
use App\Actions\ExpireBypassRequestsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Actions — Design §8 (R4.13–R4.16)
|--------------------------------------------------------------------------
|
| Expire pending bypass requests past their TTL and escalate unhandled
| requests to God Admins / email. Both run every minute with overlap
| protection to ensure idempotent execution even after missed ticks.
|
*/

Schedule::call(fn () => app(ExpireBypassRequestsAction::class)())
    ->name('expire-bypass-requests')
    ->everyMinute()->withoutOverlapping();

Schedule::call(fn () => app(EscalateBypassRequestsAction::class)())
    ->name('escalate-bypass-requests')
    ->everyMinute()->withoutOverlapping();
