<?php

namespace Tests\Unit\Services;

use App\Services\AuditService;
use Tests\TestCase;

class AuditServiceRedactionTest extends TestCase
{
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditService;
    }

    public function test_redacts_password_key(): void
    {
        $result = $this->auditService->redact([
            'username' => 'officer1',
            'password' => 'secret123',
        ]);

        $this->assertEquals('officer1', $result['username']);
        $this->assertEquals('[REDACTED]', $result['password']);
    }

    public function test_redacts_authorization_key(): void
    {
        $result = $this->auditService->redact([
            'authorization' => 'Bearer abc123',
            'content_type' => 'application/json',
        ]);

        $this->assertEquals('[REDACTED]', $result['authorization']);
        $this->assertEquals('application/json', $result['content_type']);
    }

    public function test_redacts_bearer_key(): void
    {
        $result = $this->auditService->redact([
            'bearer_token' => 'xyz789',
            'method' => 'POST',
        ]);

        $this->assertEquals('[REDACTED]', $result['bearer_token']);
        $this->assertEquals('POST', $result['method']);
    }

    public function test_redacts_token_key(): void
    {
        $result = $this->auditService->redact([
            'access_token' => 'tok_abc',
            'refresh_token' => 'ref_xyz',
            'user_id' => '123',
        ]);

        $this->assertEquals('[REDACTED]', $result['access_token']);
        $this->assertEquals('[REDACTED]', $result['refresh_token']);
        $this->assertEquals('123', $result['user_id']);
    }

    public function test_redacts_secret_key(): void
    {
        $result = $this->auditService->redact([
            'api_secret' => 'sk_live_abc',
            'client_secret' => 'cs_xyz',
            'name' => 'test',
        ]);

        $this->assertEquals('[REDACTED]', $result['api_secret']);
        $this->assertEquals('[REDACTED]', $result['client_secret']);
        $this->assertEquals('test', $result['name']);
    }

    public function test_redaction_is_case_insensitive(): void
    {
        $result = $this->auditService->redact([
            'PASSWORD' => 'upper',
            'Authorization' => 'mixed',
            'BEARER' => 'caps',
            'Token' => 'title',
            'SECRET_KEY' => 'env',
        ]);

        $this->assertEquals('[REDACTED]', $result['PASSWORD']);
        $this->assertEquals('[REDACTED]', $result['Authorization']);
        $this->assertEquals('[REDACTED]', $result['BEARER']);
        $this->assertEquals('[REDACTED]', $result['Token']);
        $this->assertEquals('[REDACTED]', $result['SECRET_KEY']);
    }

    public function test_redacts_nested_arrays_recursively(): void
    {
        $result = $this->auditService->redact([
            'user' => [
                'name' => 'John',
                'password' => 'hidden',
                'credentials' => [
                    'token' => 'nested_token',
                    'role' => 'admin',
                ],
            ],
            'action' => 'login',
        ]);

        $this->assertEquals('John', $result['user']['name']);
        $this->assertEquals('[REDACTED]', $result['user']['password']);
        $this->assertEquals('[REDACTED]', $result['user']['credentials']['token']);
        $this->assertEquals('admin', $result['user']['credentials']['role']);
        $this->assertEquals('login', $result['action']);
    }

    public function test_empty_array_returns_empty(): void
    {
        $result = $this->auditService->redact([]);
        $this->assertEquals([], $result);
    }

    public function test_non_sensitive_keys_pass_through(): void
    {
        $input = [
            'officer_id' => 'abc-123',
            'latitude' => -6.175,
            'longitude' => 106.827,
            'reason_code' => 'OUTSIDE_GEOFENCE',
            'distance_meters' => 87.3,
        ];

        $result = $this->auditService->redact($input);

        $this->assertEquals($input, $result);
    }

    public function test_partial_key_match_is_redacted(): void
    {
        $result = $this->auditService->redact([
            'user_password_hash' => 'hashed',
            'x_authorization_header' => 'Bearer xyz',
        ]);

        $this->assertEquals('[REDACTED]', $result['user_password_hash']);
        $this->assertEquals('[REDACTED]', $result['x_authorization_header']);
    }
}
