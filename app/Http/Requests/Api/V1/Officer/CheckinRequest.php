<?php

namespace App\Http\Requests\Api\V1\Officer;

use App\Exceptions\Checkin\PhotoInvalidException;
use App\Exceptions\Checkin\PhotoTooLargeException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Officer API — Check-in request (R3.1).
 * Validates the multipart check-in payload. Detailed business validation
 * (mock_location, magic bytes, etc.) occurs inside ProcessCheckinAction.
 *
 * When the photo field fails validation, the request translates the error
 * into the appropriate domain exception (PhotoInvalidException /
 * PhotoTooLargeException) so the API renders a problem+json response with
 * the correct reason_code.
 */
class CheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSizeKb = (int) round(((float) config('policehazard.photo.max_size_mb', 8)) * 1024);
        $allowedMimes = config('policehazard.photo.allowed_mimes', ['image/jpeg', 'image/png']);
        $mimeList = implode(',', array_map(
            fn (string $m) => str_replace('image/', '', $m),
            $allowedMimes,
        ));

        return [
            'assignment_id' => ['required', 'uuid'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['required', 'numeric', 'min:0'],
            'gps_altitude' => ['nullable', 'numeric'],
            'gps_speed' => ['nullable', 'numeric', 'min:0'],
            'gps_provider' => ['required', 'string', 'in:gps,network,fused'],
            'timestamp_device' => ['required', 'date'],
            'mock_location' => ['required', 'boolean'],
            'photo' => ['required', 'file', 'mimes:'.$mimeList, 'max:'.$maxSizeKb],
            'device_metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Translate photo-related validation failures into domain exceptions
     * so the ApiProblemRenderer emits the documented reason_code.
     */
    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();

        if (isset($failed['photo'])) {
            $rules = array_keys($failed['photo']);

            if (in_array('Max', $rules, true)) {
                throw new PhotoTooLargeException([
                    'max_size_mb' => (float) config('policehazard.photo.max_size_mb', 8),
                ]);
            }

            throw new PhotoInvalidException([
                'reason' => 'validation_failed',
                'rules' => $rules,
            ]);
        }

        parent::failedValidation($validator);
    }
}
