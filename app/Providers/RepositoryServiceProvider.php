<?php

namespace App\Providers;

use App\Repositories\AssignmentRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\ManualBypassApprovalRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\OperationRepositoryInterface;
use App\Repositories\Contracts\SakerRepositoryInterface;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\ZoneRepositoryInterface;
use App\Repositories\LocationRepository;
use App\Repositories\ManualBypassApprovalRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OperationRepository;
use App\Repositories\SakerRepository;
use App\Repositories\ShiftRepository;
use App\Repositories\UserRepository;
use App\Repositories\ZoneRepository;
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
            SakerRepositoryInterface::class,
            SakerRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            OperationRepositoryInterface::class,
            OperationRepository::class
        );

        $this->app->bind(
            ZoneRepositoryInterface::class,
            ZoneRepository::class
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            LocationRepository::class
        );

        $this->app->bind(
            AssignmentRepositoryInterface::class,
            AssignmentRepository::class
        );

        $this->app->bind(
            AttendanceRepositoryInterface::class,
            AttendanceRepository::class
        );

        $this->app->bind(
            AuditLogRepositoryInterface::class,
            AuditLogRepository::class
        );

        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        $this->app->bind(
            ShiftRepositoryInterface::class,
            ShiftRepository::class
        );

        $this->app->bind(
            ManualBypassApprovalRepositoryInterface::class,
            ManualBypassApprovalRepository::class
        );
    }
}
