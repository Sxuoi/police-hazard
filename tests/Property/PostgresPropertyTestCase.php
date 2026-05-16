<?php

namespace Tests\Property;

use Eris\TestTrait;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base class for Postgres-only property-based tests.
 *
 * Wraps parent::setUp() in an exception handler so we skip gracefully when:
 *   - the Postgres driver isn't available (pdo_pgsql missing),
 *   - the Postgres server is unreachable (Connection refused).
 */
abstract class PostgresPropertyTestCase extends TestCase
{
    use RefreshDatabase, TestTrait;

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (\PDOException|QueryException $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'could not find driver')) {
                $this->markTestSkipped('Postgres driver (pdo_pgsql) unavailable');
            }

            if (str_contains($msg, 'Connection refused')
                || str_contains($msg, 'connection to server')
                || str_contains($msg, 'SQLSTATE[08006]')) {
                $this->markTestSkipped('Postgres server unreachable');
            }

            throw $e;
        }

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Postgres-only');
        }
    }
}
