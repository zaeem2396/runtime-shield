<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

final class RuntimeContextBuilderTest extends TestCase
{
    public function test_build_returns_context_with_all_nulls_by_default(): void
    {
        $ctx = (new RuntimeContextBuilder())->build();

        $this->assertNull($ctx->request);
        $this->assertNull($ctx->response);
        $this->assertNull($ctx->route);
        $this->assertNull($ctx->auth);
        $this->assertSame(0.0, $ctx->processingTimeMs);
        $this->assertNotEmpty($ctx->requestId);
        $this->assertInstanceOf(\DateTimeImmutable::class, $ctx->createdAt);
    }

    public function test_build_auto_generates_request_id(): void
    {
        $id1 = (new RuntimeContextBuilder())->build()->requestId;
        $id2 = (new RuntimeContextBuilder())->build()->requestId;

        $this->assertNotSame($id1, $id2);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $id1);
    }

    public function test_with_request_id_overrides_auto_generated(): void
    {
        $ctx = (new RuntimeContextBuilder())
            ->withRequestId('custom-id')
            ->build();

        $this->assertSame('custom-id', $ctx->requestId);
    }

    public function test_with_request_sets_request_signal(): void
    {
        $signal = $this->makeRequestSignal();
        $ctx = (new RuntimeContextBuilder())->withRequest($signal)->build();

        $this->assertSame($signal, $ctx->request);
    }

    public function test_with_response_sets_response_signal(): void
    {
        $signal = $this->makeResponseSignal();
        $ctx = (new RuntimeContextBuilder())->withResponse($signal)->build();

        $this->assertSame($signal, $ctx->response);
    }

    public function test_with_route_sets_route_signal(): void
    {
        $signal = $this->makeRouteSignal();
        $ctx = (new RuntimeContextBuilder())->withRoute($signal)->build();

        $this->assertSame($signal, $ctx->route);
    }

    public function test_with_auth_sets_auth_signal(): void
    {
        $signal = AuthSignal::unauthenticated('web');
        $ctx = (new RuntimeContextBuilder())->withAuth($signal)->build();

        $this->assertSame($signal, $ctx->auth);
    }

    public function test_with_processing_time_ms(): void
    {
        $ctx = (new RuntimeContextBuilder())
            ->withProcessingTimeMs(99.9)
            ->build();

        $this->assertSame(99.9, $ctx->processingTimeMs);
    }

    public function test_method_chaining_returns_new_instance(): void
    {
        $builder = new RuntimeContextBuilder();
        $chained = $builder->withRequestId('x');

        $this->assertNotSame($builder, $chained);
    }

    public function test_all_signals_present_produces_complete_context(): void
    {
        $ctx = (new RuntimeContextBuilder())
            ->withRequest($this->makeRequestSignal())
            ->withResponse($this->makeResponseSignal())
            ->withRoute($this->makeRouteSignal())
            ->withAuth(AuthSignal::unauthenticated())
            ->withProcessingTimeMs(55.0)
            ->build();

        $this->assertTrue($ctx->isComplete());
        $this->assertSame(55.0, $ctx->processingTimeMs);
    }

    // --- helpers ---

    private function makeRequestSignal(): RequestSignal
    {
        return new RequestSignal('GET', 'http://localhost/', '/', '127.0.0.1', [], [], 0, new \DateTimeImmutable());
    }

    private function makeResponseSignal(): ResponseSignal
    {
        return new ResponseSignal(200, 'OK', [], 0, 12.5, new \DateTimeImmutable());
    }

    private function makeRouteSignal(): RouteSignal
    {
        return new RouteSignal('home', '/', 'HomeController@index', 'HomeController', [], true);
    }
}
