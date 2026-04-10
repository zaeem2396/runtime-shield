<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Signal;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Signal\AuthCollectorContract;
use RuntimeShield\Contracts\Signal\RequestCapturerContract;
use RuntimeShield\Contracts\Signal\ResponseCapturerContract;
use RuntimeShield\Contracts\Signal\RouteCollectorContract;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\NeverSampler;
use RuntimeShield\Core\Signal\InMemoryContextStore;
use RuntimeShield\Core\Signal\InMemorySignalStore;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Laravel\Signal\SignalPipeline;
use Symfony\Component\HttpFoundation\Response;

final class SignalPipelineTest extends TestCase
{
    public function test_collect_request_does_nothing_when_not_sampled(): void
    {
        $signalStore = new InMemorySignalStore();
        $contextStore = new InMemoryContextStore();
        $pipeline = $this->makePipeline(
            sampler: new NeverSampler(),
            signalStore: $signalStore,
            contextStore: $contextStore,
        );

        $pipeline->collectRequest($this->makeIlluminateRequest());

        $this->assertNull($signalStore->getRequest());
    }

    public function test_collect_request_stores_signals_when_sampled(): void
    {
        $signalStore = new InMemorySignalStore();
        $contextStore = new InMemoryContextStore();
        $pipeline = $this->makePipeline(
            sampler: new AlwaysSampler(),
            signalStore: $signalStore,
            contextStore: $contextStore,
        );

        $pipeline->collectRequest($this->makeIlluminateRequest());

        $this->assertNotNull($signalStore->getRequest());
        $this->assertNotNull($signalStore->getRoute());
        $this->assertNotNull($signalStore->getAuth());
    }

    public function test_assemble_returns_null_when_not_sampled(): void
    {
        $pipeline = $this->makePipeline(sampler: new NeverSampler());
        $pipeline->collectRequest($this->makeIlluminateRequest());

        $result = $pipeline->assemble(new Response(), 0.0);

        $this->assertNull($result);
    }

    public function test_assemble_returns_context_when_sampled(): void
    {
        $contextStore = new InMemoryContextStore();
        $pipeline = $this->makePipeline(
            sampler: new AlwaysSampler(),
            contextStore: $contextStore,
        );

        $pipeline->collectRequest($this->makeIlluminateRequest());
        $context = $pipeline->assemble(new Response(), microtime(true) * 1000.0);

        $this->assertNotNull($context);
        $this->assertTrue($contextStore->has());
        $this->assertSame($context, $contextStore->get());
    }

    public function test_reset_clears_sampling_flag_and_stores(): void
    {
        $signalStore = new InMemorySignalStore();
        $contextStore = new InMemoryContextStore();
        $pipeline = $this->makePipeline(
            sampler: new AlwaysSampler(),
            signalStore: $signalStore,
            contextStore: $contextStore,
        );

        $pipeline->collectRequest($this->makeIlluminateRequest());
        $pipeline->assemble(new Response(), 0.0);
        $pipeline->reset();

        $this->assertNull($signalStore->getRequest());
        $this->assertFalse($contextStore->has());

        // After reset, assemble should return null (sampling flag cleared)
        $result = $pipeline->assemble(new Response(), 0.0);
        $this->assertNull($result);
    }

    // --- helpers ---

    private function makePipeline(
        \RuntimeShield\Contracts\SamplerContract $sampler = new AlwaysSampler(),
        InMemorySignalStore $signalStore = new InMemorySignalStore(),
        InMemoryContextStore $contextStore = new InMemoryContextStore(),
    ): SignalPipeline {
        $requestCapturer = $this->createMock(RequestCapturerContract::class);
        $requestCapturer->method('capture')->willReturn(
            new RequestSignal('GET', 'http://localhost/', '/', '127.0.0.1', [], [], 0, new \DateTimeImmutable()),
        );

        $responseCapturer = $this->createMock(ResponseCapturerContract::class);
        $responseCapturer->method('capture')->willReturn(
            new ResponseSignal(200, 'OK', [], 0, 5.0, new \DateTimeImmutable()),
        );

        $routeCollector = $this->createMock(RouteCollectorContract::class);
        $routeCollector->method('collect')->willReturn(
            new RouteSignal('home', '/', 'HomeController@index', 'HomeController', [], true),
        );

        $authCollector = $this->createMock(AuthCollectorContract::class);
        $authCollector->method('collect')->willReturn(
            AuthSignal::unauthenticated('web'),
        );

        return new SignalPipeline(
            sampler: $sampler,
            signalStore: $signalStore,
            contextStore: $contextStore,
            requestCapturer: $requestCapturer,
            responseCapturer: $responseCapturer,
            routeCollector: $routeCollector,
            authCollector: $authCollector,
        );
    }

    private function makeIlluminateRequest(): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('http://localhost/', 'GET');
    }
}
