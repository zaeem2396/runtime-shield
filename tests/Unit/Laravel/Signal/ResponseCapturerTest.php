<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\Laravel\Signal\ResponseCapturer;
use Symfony\Component\HttpFoundation\Response;

final class ResponseCapturerTest extends TestCase
{
    private ResponseCapturer $capturer;

    #[Test]
    public function it_captures_the_status_code(): void
    {
        $response = new Response('', 404);
        $signal = $this->capturer->capture($response, microtime(true) * 1000.0);

        $this->assertSame(404, $signal->statusCode);
    }

    #[Test]
    public function it_captures_the_status_text(): void
    {
        $response = new Response('', 200);
        $signal = $this->capturer->capture($response, microtime(true) * 1000.0);

        $this->assertSame('OK', $signal->statusText);
    }

    #[Test]
    public function it_captures_body_size(): void
    {
        $body = 'Hello World';
        $response = new Response($body);
        $signal = $this->capturer->capture($response, microtime(true) * 1000.0);

        $this->assertSame(strlen($body), $signal->bodySize);
    }

    #[Test]
    public function it_calculates_a_non_negative_response_time(): void
    {
        $startMs = microtime(true) * 1000.0;
        $signal = $this->capturer->capture(new Response('ok'), $startMs);

        $this->assertGreaterThanOrEqual(0.0, $signal->responseTimeMs);
    }

    #[Test]
    public function it_returns_a_response_signal_instance(): void
    {
        $signal = $this->capturer->capture(new Response(), microtime(true) * 1000.0);

        $this->assertInstanceOf(ResponseSignal::class, $signal);
    }

    #[Test]
    public function it_normalizes_response_headers(): void
    {
        $response = new Response('', 200, ['Content-Type' => 'application/json']);
        $signal = $this->capturer->capture($response, microtime(true) * 1000.0);

        $this->assertArrayHasKey('content-type', $signal->headers);
        $this->assertIsString($signal->headers['content-type']);
    }

    protected function setUp(): void
    {
        $this->capturer = new ResponseCapturer();
    }
}
