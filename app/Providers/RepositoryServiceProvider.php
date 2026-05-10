<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * PRD §20.7 — Binds all repository interfaces to concrete implementations.
 * Direct Eloquent calls in Controllers are forbidden (PRD §3.2).
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\SakerRepositoryInterface::class,
            \App\Repositories\SakerRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\OperationRepositoryInterface::class,
            \App\Repositories\OperationRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ZoneRepositoryInterface::class,
            \App\Repositories\ZoneRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\LocationRepositoryInterface::class,
            \App\Repositories\LocationRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\AssignmentRepositoryInterface::class,
            \App\Repositories\AssignmentRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\AttendanceRepositoryInterface::class,
            \App\Repositories\AttendanceRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\AuditLogRepositoryInterface::class,
            \App\Repositories\AuditLogRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\NotificationRepositoryInterface::class,
            \App\Repositories\NotificationRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ShiftRepositoryInterface::class,
            \App\Repositories\ShiftRepository::class
        );
    }
}
