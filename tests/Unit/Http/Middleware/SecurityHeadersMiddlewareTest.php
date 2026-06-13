<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware;
    }

    public function test_adds_security_headers_to_response(): void
    {
        $request = Request::create('/test', 'GET');
        $next = fn () => new Response('OK');

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($response->headers->has('X-Request-ID'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertEquals('geolocation=(self), camera=(self)', $response->headers->get('Permissions-Policy'));
    }

    public function test_x_request_id_is_uuid_v7_format(): void
    {
        $request = Request::create('/test', 'GET');
        $next = fn () => new Response('OK');

        $response = $this->middleware->handle($request, $next);

        $requestId = $response->headers->get('X-Request-ID');

        // UUID format: 8-4-4-4-12 hex characters
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $requestId
        );
    }

    public function test_hsts_header_only_in_production(): void
    {
        // Production: HSTS header should be present
        app()->detectEnvironment(fn () => 'production');

        $request = Request::create('/test', 'GET');
        $next = fn () => new Response('OK');

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($response->headers->has('Strict-Transport-Security'));
        $this->assertEquals(
            'max-age=31536000; includeSubDomains',
            $response->headers->get('Strict-Transport-Security')
        );

        // Local: HSTS header should be absent
        app()->detectEnvironment(fn () => 'local');

        $response = $this->middleware->handle($request, $next);

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_request_id_stashed_on_request_attributes(): void
    {
        $request = Request::create('/test', 'GET');
        $next = fn () => new Response('OK');

        $this->middleware->handle($request, $next);

        $requestId = $request->attributes->get('request_id');

        $this->assertNotNull($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $requestId
        );
    }
}
