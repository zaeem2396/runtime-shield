<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

final class SecurityRuntimeContextTest extends TestCase
{
    public function test_construction_with_minimal_fields(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'abc123',
            createdAt: new \DateTimeImmutable('2026-01-01T00:00:00Z'),
        );

        $this->assertSame('abc123', $ctx->requestId);
        $this->assertInstanceOf(\DateTimeImmutable::class, $ctx->createdAt);
        $this->assertSame(0.0, $ctx->processingTimeMs);
        $this->assertNull($ctx->request);
        $this->assertNull($ctx->response);
        $this->assertNull($ctx->route);
        $this->assertNull($ctx->auth);
    }

    public function test_has_request_returns_false_when_null(): void
    {
        $ctx = new SecurityRuntimeContext('id', new \DateTimeImmutable());

        $this->assertFalse($ctx->hasRequest());
    }

    public function test_has_request_returns_true_when_set(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            request: $this->makeRequestSignal(),
        );

        $this->assertTrue($ctx->hasRequest());
    }

    public function test_has_response_returns_false_when_null(): void
    {
        $ctx = new SecurityRuntimeContext('id', new \DateTimeImmutable());

        $this->assertFalse($ctx->hasResponse());
    }

    public function test_has_response_returns_true_when_set(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            response: $this->makeResponseSignal(),
        );

        $this->assertTrue($ctx->hasResponse());
    }

    public function test_has_route_and_has_auth(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            route: $this->makeRouteSignal(),
            auth: AuthSignal::unauthenticated('web'),
        );

        $this->assertTrue($ctx->hasRoute());
        $this->assertTrue($ctx->hasAuth());
    }

    public function test_is_complete_false_when_any_signal_missing(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            request: $this->makeRequestSignal(),
            response: $this->makeResponseSignal(),
            route: $this->makeRouteSignal(),
        );

        $this->assertFalse($ctx->isComplete());
    }

    public function test_is_complete_true_when_all_signals_present(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            request: $this->makeRequestSignal(),
            response: $this->makeResponseSignal(),
            route: $this->makeRouteSignal(),
            auth: AuthSignal::unauthenticated('web'),
        );

        $this->assertTrue($ctx->isComplete());
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'test-id',
            createdAt: new \DateTimeImmutable('2026-01-01T12:00:00+00:00'),
            processingTimeMs: 42.5,
        );

        $arr = $ctx->toArray();

        $this->assertSame('test-id', $arr['request_id']);
        $this->assertSame(42.5, $arr['processing_time_ms']);
        $this->assertFalse($arr['is_complete']);
        $this->assertNull($arr['request']);
        $this->assertNull($arr['response']);
        $this->assertNull($arr['route']);
        $this->assertNull($arr['auth']);
        $this->assertArrayHasKey('created_at', $arr);
    }

    public function test_to_array_serializes_request_signal(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            request: $this->makeRequestSignal(),
        );

        $arr = $ctx->toArray();

        $this->assertIsArray($arr['request']);
        $this->assertSame('GET', $arr['request']['method']);
        $this->assertSame('/test', $arr['request']['path']);
    }

    public function test_to_array_serializes_response_signal(): void
    {
        $ctx = new SecurityRuntimeContext(
            requestId: 'id',
            createdAt: new \DateTimeImmutable(),
            response: $this->makeResponseSignal(),
        );

        $arr = $ctx->toArray();

        $this->assertIsArray($arr['response']);
        $this->assertSame(200, $arr['response']['status_code']);
    }

    // --- helpers ---

    private function makeRequestSignal(): RequestSignal
    {
        return new RequestSignal(
            method: 'GET',
            url: 'http://localhost/test',
            path: '/test',
            ip: '127.0.0.1',
            headers: [],
            query: [],
            bodySize: 0,
            capturedAt: new \DateTimeImmutable(),
        );
    }

    private function makeResponseSignal(): ResponseSignal
    {
        return new ResponseSignal(
            statusCode: 200,
            statusText: 'OK',
            headers: [],
            bodySize: 0,
            responseTimeMs: 10.0,
            capturedAt: new \DateTimeImmutable(),
        );
    }

    private function makeRouteSignal(): RouteSignal
    {
        return new RouteSignal(
            name: 'home',
            uri: '/',
            action: 'HomeController@index',
            controller: 'HomeController',
            middleware: [],
            hasNamedRoute: true,
        );
    }
}
