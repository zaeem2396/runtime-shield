<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Signal\SignalNormalizer;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;

final class SignalNormalizerTest extends TestCase
{
    private SignalNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new SignalNormalizer();
    }

    #[Test]
    public function normalize_request_returns_a_request_signal(): void
    {
        $signal = $this->normalizer->normalizeRequest(['method' => 'GET', 'url' => 'https://x.com']);

        $this->assertInstanceOf(RequestSignal::class, $signal);
        $this->assertSame('GET', $signal->method);
    }

    #[Test]
    public function normalize_request_with_empty_array_uses_defaults(): void
    {
        $signal = $this->normalizer->normalizeRequest([]);

        $this->assertSame('GET', $signal->method);
        $this->assertSame('/', $signal->path);
        $this->assertSame(0, $signal->bodySize);
    }

    #[Test]
    public function normalize_request_uppercases_method(): void
    {
        $signal = $this->normalizer->normalizeRequest(['method' => 'put']);

        $this->assertSame('PUT', $signal->method);
    }

    #[Test]
    public function normalize_response_returns_a_response_signal(): void
    {
        $signal = $this->normalizer->normalizeResponse(['status_code' => 422, 'status_text' => 'Unprocessable Content']);

        $this->assertInstanceOf(ResponseSignal::class, $signal);
        $this->assertSame(422, $signal->statusCode);
        $this->assertSame('Unprocessable Content', $signal->statusText);
    }

    #[Test]
    public function normalize_response_with_empty_array_uses_defaults(): void
    {
        $signal = $this->normalizer->normalizeResponse([]);

        $this->assertSame(200, $signal->statusCode);
        $this->assertSame(0.0, $signal->responseTimeMs);
    }

    #[Test]
    public function normalize_response_casts_numeric_response_time(): void
    {
        $signal = $this->normalizer->normalizeResponse(['response_time_ms' => '75.3']);

        $this->assertSame(75.3, $signal->responseTimeMs);
    }
}
