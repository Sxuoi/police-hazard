<?php

namespace App\Http\Requests\Api\V1\Officer;

use App\Exceptions\Bypass\OfficerNoteRequiredException;
use App\Exceptions\Checkin\PhotoInvalidException;
use App\Exceptions\Checkin\PhotoTooLargeException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Officer API — Bypass request form (R4.2).
 * Validates the multipart bypass payload. Additional domain checks
 * (eligible reason code, mock_location, photo magic bytes) run inside
 * CreateBypassRequestAction.
 *
 * When the photo or officer_note fields fail validation, the request
 * translates the error into the appropriate domain exception so the API
 * renders a problem+json response with the correct reason_code.
 */
class BypassRequestRequest extends FormRequest
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
            'reason_code' => [
                'required',
                'string',
                'in:OUTSIDE_SHIFT_WINDOW,OUTSIDE_GEOFENCE,SPOOFING_REJECTED',
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['required', 'numeric', 'min:0'],
            'gps_altitude' => ['nullable', 'numeric'],
            'gps_speed' => ['nullable', 'numeric', 'min:0'],
            'gps_provider' => ['required', 'string', 'in:gps,network,fused'],
            'timestamp_device' => ['required', 'date'],
            'mock_location' => ['required', 'boolean'],
            'photo' => ['required', 'file', 'mimes:'.$mimeList, 'max:'.$maxSizeKb],
            'officer_note' => ['required', 'string', 'min:20'],
            'device_metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Translate photo- and officer_note-related validation failures into
     * domain exceptions so the ApiProblemRenderer emits the documented
     * reason_code.
     */
    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();

        if (isset($failed['officer_note'])) {
            $rules = array_keys($failed['officer_note']);

            if (array_intersect(['Min', 'Required', 'String'], $rules)) {
                throw new OfficerNoteRequiredException([
                    'min_length' => 20,
                ]);
            }
        }

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
