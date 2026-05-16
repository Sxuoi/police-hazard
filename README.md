# Police Hazard — Command & Control Attendance Management System

**Police Hazard (PH)** is a mission-critical, web-based command-and-control platform designed for law enforcement agencies. It manages, verifies, and audits officer attendance at static patrol points and mobile routes, replacing unverifiable manual attendance with GPS-verified, photo-documented digital check-ins.

## Features & Complexity

This project is built with a high degree of complexity and strict compliance requirements. It ensures zero cross-tenant data leaks and provides a tamper-evident audit trail.

*   **Geospatial Validation:** Uses PostGIS geofencing (`ST_DWithin`) to validate an officer's check-in coordinates against the assigned location's radius.
*   **Tamper-Evident Audit Trails:** Implements immutable logging at the database level. Attendance records and audit logs are append-only.
*   **Advanced Multi-Tenancy:** Three-layer tenant isolation mechanism using PostgreSQL Row-Level Security (RLS), Eloquent Global Query Scopes, and Application Middleware to manage units across organizational hierarchies (POLDA, POLRESTABES, POLSEK).
*   **Spoofing Detection:** Checks for mock location flags, GPS accuracy thresholds, and timestamp drifts.
*   **Role-Based Access Control:** Distinct roles including God Admin (cross-Saker oversight), Saker Admin (unit-level management), and Officer (mobile web check-in).
*   **Server-Side Image Processing:** Automatic asynchronous photo watermarking via Intervention Image for check-ins.
*   **Real-time Dashboard:** Live interactive map tracking officer attendance and coverage statuses via Leaflet.js.

## Technology Stack

*   **Backend Framework:** PHP 8.3 / Laravel 11.x
*   **Database:** PostgreSQL 16 + PostGIS 3.4
*   **Queue & Cache:** Redis + Laravel Horizon
*   **Frontend (Admin):** Blade Templates + Alpine.js + Tailwind CSS
*   **Frontend (Mobile Officer):** Responsive Blade + Alpine.js leveraging browser Geolocation and MediaDevices (Camera) APIs
*   **Mapping:** Leaflet.js + OpenStreetMap (with Marker Clustering)
*   **Authentication:** Laravel Session (Admin) / Laravel Sanctum (Officer API)
*   **File Storage:** S3-compatible Object Storage (MinIO/AWS S3)

## Architecture Patterns

*   **Service + Action + Repository Pattern:** Strict separation of concerns where business logic is confined to Action classes, orchestrated by Services, and database queries are abstracted behind Repositories.
*   **Database Immutability:** Uses PostgreSQL rules to block `UPDATE` and `DELETE` operations on critical tables (`attendances`, `audit_logs`).
*   **UUID v7 Primary Keys:** Utilizes time-ordered UUIDs to prevent B-tree index fragmentation in high-volume tables.

## Requirements

*   PHP >= 8.3
*   PostgreSQL >= 16 with PostGIS extension enabled
*   Redis
*   Composer
*   Node.js & NPM

## Installation

1.  Clone the repository and install dependencies:
    ```bash
    composer install
    npm install
    ```
2.  Copy the environment file and generate an application key:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
3.  Configure your `.env` file with PostgreSQL and Redis connection details. Ensure PostGIS is enabled on your database.
4.  Run database migrations and seeders:
    ```bash
    php artisan migrate:fresh --seed
    ```
5.  Build frontend assets using Vite:
    ```bash
    npm run dev # or npm run build
    ```
6.  Start the development server and queue worker:
    ```bash
    php artisan serve
    php artisan queue:listen
    ```
