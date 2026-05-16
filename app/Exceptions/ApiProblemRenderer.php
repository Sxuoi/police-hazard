<?php

namespace App\Exceptions;

use App\Exceptions\Bypass\BypassException;
use App\Exceptions\Checkin\CheckinException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Design §2.4 — RFC 7807 problem+json renderer for the Officer API.
 *
 * Maps any CheckinException or BypassException (and their subclasses)
 * polymorphically to a problem+json response carrying:
 *   type, title, status, detail, instance, reason_code,
 *   bypass_eligible, request_id, plus any `extra` fields on the exception.
 *
 * Wired via bootstrap/app.php withExceptions(...)->render(...).
 */
class ApiProblemRenderer
{
    /**
     * Render the exception to a JsonResponse if it's one we handle;
     * return null to let Laravel's default handler take over.
     */
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        // Only handle /api/* requests — web routes use HTML errors.
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        if ($e instanceof AuthenticationException && $request->is('api/*')) {
            return $this->renderAuthenticationException($request);
        }

        if ($e instanceof ThrottleRequestsException && $request->is('api/*')) {
            return $this->renderThrottleException($e, $request);
        }

        if (! ($e instanceof CheckinException) && ! ($e instanceof BypassException)) {
            return null;
        }

        /** @var CheckinException|BypassException $e */
        $reasonCode = $e->reasonCode;
        $status = $e->httpStatus;
        $bypassEligible = $e->bypassEligible;
        $extra = $e->extra;

        $payload = [
            'type' => 'https://policehazard.local/errors/'.$reasonCode,
            'title' => $this->titleFor($reasonCode),
            'status' => $status,
            'detail' => $this->detailFor($reasonCode, $extra),
            'instance' => $request->path() !== '' ? '/'.ltrim($request->path(), '/') : null,
            'reason_code' => $reasonCode,
            'bypass_eligible' => $bypassEligible,
            'request_id' => $request->attributes->get('request_id'),
        ];

        foreach ($extra as $key => $value) {
            if (! array_key_exists($key, $payload)) {
                $payload[$key] = $value;
            }
        }

        return new JsonResponse(
            $payload,
            $status,
            ['Content-Type' => 'application/problem+json'],
        );
    }

    /**
     * Map Laravel's AuthenticationException to the TOKEN_INVALID envelope.
     */
    private function renderAuthenticationException(Request $request): JsonResponse
    {
        return new JsonResponse([
            'type' => 'https://policehazard.local/errors/TOKEN_INVALID',
            'title' => $this->titleFor('TOKEN_INVALID'),
            'status' => 401,
            'detail' => $this->detailFor('TOKEN_INVALID', []),
            'instance' => $request->path() !== '' ? '/'.ltrim($request->path(), '/') : null,
            'reason_code' => 'TOKEN_INVALID',
            'bypass_eligible' => false,
            'request_id' => $request->attributes->get('request_id'),
        ], 401, ['Content-Type' => 'application/problem+json']);
    }

    /**
     * Design §12 — Map ThrottleRequestsException to RATE_LIMITED problem+json.
     */
    private function renderThrottleException(ThrottleRequestsException $e, Request $request): JsonResponse
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

        return new JsonResponse([
            'type' => 'https://policehazard.local/errors/RATE_LIMITED',
            'title' => 'Terlalu banyak permintaan',
            'status' => 429,
            'detail' => 'Anda telah melebihi batas permintaan. Silakan coba lagi nanti.',
            'instance' => $request->path() !== '' ? '/'.ltrim($request->path(), '/') : null,
            'reason_code' => 'RATE_LIMITED',
            'retry_after_seconds' => $retryAfter !== null ? (int) $retryAfter : null,
            'request_id' => $request->attributes->get('request_id'),
        ], 429, ['Content-Type' => 'application/problem+json']);
    }

    private function titleFor(string $reasonCode): string
    {
        return match ($reasonCode) {
            'INVALID_CREDENTIALS' => 'Kredensial tidak valid',
            'ACCOUNT_DISABLED' => 'Akun dinonaktifkan',
            'ACCOUNT_LOCKED' => 'Akun terkunci',
            'TOKEN_INVALID' => 'Token tidak valid',
            'RATE_LIMITED' => 'Terlalu banyak permintaan',
            'ASSIGNMENT_NOT_FOUND' => 'Penugasan tidak ditemukan',
            'OUTSIDE_SHIFT_WINDOW' => 'Di luar jam shift',
            'MOCK_LOCATION_DETECTED' => 'Mock location terdeteksi',
            'OUTSIDE_GEOFENCE' => 'Di luar geofence',
            'SPOOFING_REJECTED' => 'Indikasi spoofing',
            'CHECKIN_ALREADY_COMPLETED' => 'Absensi sudah tercatat',
            'PHOTO_INVALID' => 'Foto tidak valid',
            'PHOTO_TOO_LARGE' => 'Ukuran foto melebihi batas',
            'BYPASS_DECISION_ALREADY_MADE' => 'Keputusan bypass sudah dibuat',
            'BYPASS_EXPIRED' => 'Bypass telah kedaluwarsa',
            'BYPASS_PHOTO_MISSING' => 'Foto bypass diperlukan',
            'OFFICER_NOTE_REQUIRED' => 'Keterangan petugas minimal 20 karakter',
            'SUPERVISOR_NOTE_REQUIRED' => 'Keterangan supervisor minimal 20 karakter',
            'MOCK_LOCATION_NEVER_BYPASSABLE' => 'Mock location tidak bisa di-bypass',
            'REASON_CODE_NOT_BYPASS_ELIGIBLE' => 'Kode alasan tidak memenuhi syarat bypass',
            default => 'Permintaan ditolak',
        };
    }

    private function detailFor(string $reasonCode, array $extra): string
    {
        return match ($reasonCode) {
            'OUTSIDE_GEOFENCE' => isset($extra['distance_meters'])
                ? "Lokasi Anda {$extra['distance_meters']} meter dari titik tugas."
                : 'Posisi Anda berada di luar radius geofence.',
            'OUTSIDE_SHIFT_WINDOW' => 'Saat ini berada di luar jendela shift penugasan.',
            'MOCK_LOCATION_DETECTED' => 'Aplikasi mendeteksi penggunaan mock location.',
            'SPOOFING_REJECTED' => 'Terdeteksi beberapa sinyal yang mengindikasikan manipulasi lokasi.',
            'CHECKIN_ALREADY_COMPLETED' => 'Anda sudah melakukan check-in untuk penugasan ini.',
            'PHOTO_TOO_LARGE' => isset($extra['max_size_mb'])
                ? "Ukuran foto melebihi batas {$extra['max_size_mb']} MB."
                : 'Ukuran foto melebihi batas yang diizinkan.',
            'PHOTO_INVALID' => 'Foto tidak dapat diproses. Pastikan format JPEG/PNG valid.',
            'ASSIGNMENT_NOT_FOUND' => 'Penugasan tidak ditemukan atau bukan milik Anda.',
            'INVALID_CREDENTIALS' => 'NRP atau kata sandi salah.',
            'ACCOUNT_DISABLED' => 'Akun Anda tidak aktif. Hubungi administrator.',
            'TOKEN_INVALID' => 'Token otentikasi tidak valid atau kedaluwarsa.',
            'OFFICER_NOTE_REQUIRED' => isset($extra['min_length'])
                ? "Keterangan minimal {$extra['min_length']} karakter."
                : 'Keterangan petugas diperlukan.',
            'MOCK_LOCATION_NEVER_BYPASSABLE' => 'Mock location tidak pernah dapat di-bypass.',
            'REASON_CODE_NOT_BYPASS_ELIGIBLE' => 'Kode alasan bukan termasuk yang dapat di-bypass.',
            default => 'Permintaan ditolak oleh sistem.',
        };
    }
}
