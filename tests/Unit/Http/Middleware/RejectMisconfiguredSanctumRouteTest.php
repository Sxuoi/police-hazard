<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RejectMisconfiguredSanctumRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Tests\TestCase;

class RejectMisconfiguredSanctumRouteTest extends TestCase
{
    private RejectMisconfiguredSanctumRoute $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RejectMisconfiguredSanctumRoute;
    }

    public function test_passes_through_for_non_flagged_routes(): void
    {
        // Empty flagged list
        app()->instance('ph.sanctum_routes_missing_saker_context', []);

        $request = Request::create('/api/v1/officer/assignments', 'GET');
        $route = new Route('GET', 'api/v1/officer/assignments', fn () => null);
        $request->setRouteResolver(fn () => $route);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return new Response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_returns_500_problem_json_for_flagged_route(): void
    {
        // Flag a route URI as misconfigured
        $flaggedUri = 'api/v1/officer/dangerous';
        app()->instance('ph.sanctum_routes_missing_saker_context', [$flaggedUri]);

        $request = Request::create('/api/v1/officer/dangerous', 'GET');
        $request->attributes->set('request_id', '01912345-6789-7abc-8def-0123456789ab');

        $route = new Route('GET', $flaggedUri, fn () => null);
        $request->setRouteResolver(fn () => $route);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return new Response('OK');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertFalse($nextCalled);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));

        $body = json_decode($response->getContent(), true);

        $this->assertEquals('MIDDLEWARE_MISCONFIGURED', $body['reason_code']);
        $this->assertEquals(500, $body['status']);
        $this->assertEquals('https://policehazard.local/errors/MIDDLEWARE_MISCONFIGURED', $body['type']);
        $this->assertEquals('Route middleware misconfigured', $body['title']);
        $this->assertEquals('01912345-6789-7abc-8def-0123456789ab', $body['request_id']);
    }
}
