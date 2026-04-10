<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Signal\ResponseSignal;

final class ResponseSignalTest extends TestCase
{
    #[Test]
    public function it_creates_from_array_with_all_fields(): void
    {
        $capturedAt = new \DateTimeImmutable('2026-04-09 12:00:00');

        $signal = ResponseSignal::fromArray([
            'status_code' => 201,
            'status_text' => 'Created',
            'headers' => ['content-type' => 'application/json'],
            'body_size' => 512,
            'response_time_ms' => 42.5,
            'captured_at' => $capturedAt,
        ]);

        $this->assertSame(201, $signal->statusCode);
        $this->assertSame('Created', $signal->statusText);
        $this->assertSame(['content-type' => 'application/json'], $signal->headers);
        $this->assertSame(512, $signal->bodySize);
        $this->assertSame(42.5, $signal->responseTimeMs);
        $this->assertSame($capturedAt, $signal->capturedAt);
    }

    #[Test]
    public function it_applies_safe_defaults_for_missing_keys(): void
    {
        $signal = ResponseSignal::fromArray([]);

        $this->assertSame(200, $signal->statusCode);
        $this->assertSame('', $signal->statusText);
        $this->assertSame([], $signal->headers);
        $this->assertSame(0, $signal->bodySize);
        $this->assertSame(0.0, $signal->responseTimeMs);
        $this->assertInstanceOf(\DateTimeImmutable::class, $signal->capturedAt);
    }

    #[Test]
    public function it_casts_string_response_time_to_float(): void
    {
        $signal = ResponseSignal::fromArray(['response_time_ms' => '123.4']);

        $this->assertSame(123.4, $signal->responseTimeMs);
    }

    #[Test]
    public function it_falls_back_to_zero_for_non_numeric_response_time(): void
    {
        $signal = ResponseSignal::fromArray(['response_time_ms' => 'fast']);

        $this->assertSame(0.0, $signal->responseTimeMs);
    }

    #[Test]
    public function it_can_be_constructed_directly(): void
    {
        $now = new \DateTimeImmutable();
        $signal = new ResponseSignal(404, 'Not Found', [], 0, 5.1, $now);

        $this->assertSame(404, $signal->statusCode);
        $this->assertSame('Not Found', $signal->statusText);
        $this->assertSame(5.1, $signal->responseTimeMs);
        $this->assertSame($now, $signal->capturedAt);
    }
}
