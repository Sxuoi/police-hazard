<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureSakerContext;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureSakerContextTest extends TestCase
{
    private EnsureSakerContext $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureSakerContext;
    }

    public function test_resolves_saker_id_from_authenticated_user(): void
    {
        $user = new User;
        $user->role = 'officer';
        $user->saker_id = 'saker-uuid-123';

        $request = Request::create('/api/v1/officer/assignments', 'GET');
        $request->setUserResolver(fn () => $user);

        $next = fn ($req) => new Response('OK');

        $this->middleware->handle($request, $next);

        $this->assertEquals('saker-uuid-123', $request->attributes->get('saker_id'));
    }

    public function test_god_admin_bypasses_tenant_check(): void
    {
        $user = new User;
        $user->role = 'god_admin';
        $user->saker_id = null;

        $request = Request::create('/api/v1/officer/assignments', 'GET');
        $request->setUserResolver(fn () => $user);

        $next = fn ($req) => new Response('OK');

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        // God admin should NOT have saker_id restriction set
        $this->assertNull($request->attributes->get('saker_id'));
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $request = Request::create('/api/v1/officer/assignments', 'GET');
        $request->setUserResolver(fn () => null);

        $next = fn ($req) => new Response('OK');

        $this->expectException(HttpException::class);

        try {
            $this->middleware->handle($request, $next);
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            throw $e;
        }
    }
}
