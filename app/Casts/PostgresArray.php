<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast for PostgreSQL native arrays (e.g., SMALLINT[], TEXT[]).
 * Converts between PHP arrays and PostgreSQL array literal format {1,2,3}.
 */
class PostgresArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        // PostgreSQL returns "{1,2,3,4,5}" format
        $value = trim($value, '{}');
        if ($value === '') {
            return [];
        }

        return array_map('intval', explode(',', $value));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return '{'.implode(',', $value).'}';
        }

        return $value;
    }
}
