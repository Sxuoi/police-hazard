<?php

namespace Tests\Unit\Actions;

use App\Actions\AuthenticateOfficerAction;
use App\Exceptions\Checkin\CheckinException;
use App\Models\Saker;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class AuthenticateOfficerActionTest extends TestCase
{
    use DatabaseTransactions;

    private $auditService;

    private AuthenticateOfficerAction $action;

    private string $sakerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = Mockery::mock(AuditService::class);
        $this->auditService->shouldReceive('log')->byDefault();

        $this->action = new AuthenticateOfficerAction($this->auditService);

        // Create a Saker for the test users
        $saker = new Saker;
        $saker->id = Uuid::uuid7()->toString();
        $saker->name = 'Test Saker';
        $saker->code = 'TST';
        $saker->type = 'POLSEK';
        $saker->email = 'test-saker@example.com';
        $saker->password = bcrypt('password');
        $saker->is_active = true;
        $saker->save();
        $this->sakerId = $saker->id;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_successful_login_returns_token_and_officer_profile(): void
    {
        User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerId,
            'name' => 'Officer Test',
            'nrp' => '100001',
            'email' => 'officer-success@test.com',
            'role' => 'officer',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $request = Request::create('/api/v1/auth/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $result = ($this->action)('100001', 'password123', $request);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('token_expires_at', $result);
        $this->assertArrayHasKey('officer', $result);
    }

    public function test_invalid_credentials_throws_with_reason_code(): void
    {
        $this->auditService->shouldReceive('log')
            ->with('OFFICER_LOGIN_FAILED', null, Mockery::type('array'))
            ->once();

        $request = Request::create('/api/v1/auth/login', 'POST');

        try {
            ($this->action)('999999', 'wrongpass', $request);
            $this->fail('Expected CheckinException was not thrown');
        } catch (CheckinException $e) {
            $this->assertEquals('INVALID_CREDENTIALS', $e->reasonCode);
            $this->assertEquals(401, $e->httpStatus);
            $this->assertFalse($e->bypassEligible);
        }
    }

    public function test_inactive_user_throws_account_disabled(): void
    {
        User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerId,
            'name' => 'Inactive Officer',
            'nrp' => '100002',
            'email' => 'inactive-officer@test.com',
            'role' => 'officer',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $this->auditService->shouldReceive('log')
            ->with('OFFICER_LOGIN_FAILED', Mockery::on(fn ($u) => $u instanceof User), Mockery::type('array'))
            ->once();

        $request = Request::create('/api/v1/auth/login', 'POST');

        try {
            ($this->action)('100002', 'password123', $request);
            $this->fail('Expected CheckinException was not thrown');
        } catch (CheckinException $e) {
            $this->assertEquals('ACCOUNT_DISABLED', $e->reasonCode);
            $this->assertEquals(403, $e->httpStatus);
            $this->assertFalse($e->bypassEligible);
        }
    }

    public function test_non_officer_role_throws_account_disabled(): void
    {
        User::withoutGlobalScopes()->create([
            'id' => Uuid::uuid7()->toString(),
            'saker_id' => $this->sakerId,
            'name' => 'Admin User',
            'nrp' => '100003',
            'email' => 'admin-user@test.com',
            'role' => 'saker_admin',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->auditService->shouldReceive('log')
            ->with('OFFICER_LOGIN_FAILED', Mockery::on(fn ($u) => $u instanceof User), Mockery::type('array'))
            ->once();

        $request = Request::create('/api/v1/auth/login', 'POST');

        try {
            ($this->action)('100003', 'password123', $request);
            $this->fail('Expected CheckinException was not thrown');
        } catch (CheckinException $e) {
            $this->assertEquals('ACCOUNT_DISABLED', $e->reasonCode);
            $this->assertEquals(403, $e->httpStatus);
            $this->assertFalse($e->bypassEligible);
        }
    }
}
