<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Signal\RequestSignal;

final class RequestSignalTest extends TestCase
{
    #[Test]
    public function it_creates_from_array_with_all_fields(): void
    {
        $capturedAt = new \DateTimeImmutable('2026-04-09 12:00:00');

        $signal = RequestSignal::fromArray([
            'method' => 'post',
            'url' => 'https://example.com/api/users',
            'path' => 'api/users',
            'ip' => '10.0.0.1',
            'headers' => ['accept' => 'application/json'],
            'query' => ['page' => '2'],
            'body_size' => 256,
            'captured_at' => $capturedAt,
        ]);

        $this->assertSame('POST', $signal->method);
        $this->assertSame('https://example.com/api/users', $signal->url);
        $this->assertSame('api/users', $signal->path);
        $this->assertSame('10.0.0.1', $signal->ip);
        $this->assertSame(['accept' => 'application/json'], $signal->headers);
        $this->assertSame(['page' => '2'], $signal->query);
        $this->assertSame(256, $signal->bodySize);
        $this->assertSame($capturedAt, $signal->capturedAt);
    }

    #[Test]
    public function it_applies_safe_defaults_for_missing_keys(): void
    {
        $signal = RequestSignal::fromArray([]);

        $this->assertSame('GET', $signal->method);
        $this->assertSame('', $signal->url);
        $this->assertSame('/', $signal->path);
        $this->assertSame('', $signal->ip);
        $this->assertSame([], $signal->headers);
        $this->assertSame([], $signal->query);
        $this->assertSame(0, $signal->bodySize);
        $this->assertInstanceOf(\DateTimeImmutable::class, $signal->capturedAt);
    }

    #[Test]
    public function it_uppercases_the_http_method(): void
    {
        $this->assertSame('DELETE', RequestSignal::fromArray(['method' => 'delete'])->method);
        $this->assertSame('PATCH', RequestSignal::fromArray(['method' => 'patch'])->method);
    }

    #[Test]
    public function it_falls_back_to_new_datetime_when_captured_at_is_not_a_datetime_immutable(): void
    {
        $before = new \DateTimeImmutable();
        $signal = RequestSignal::fromArray(['captured_at' => 'not-a-date']);
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $signal->capturedAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $signal->capturedAt->getTimestamp());
    }

    #[Test]
    public function it_casts_non_array_headers_to_empty_array(): void
    {
        $signal = RequestSignal::fromArray(['headers' => 'not-an-array']);

        $this->assertSame([], $signal->headers);
    }

    #[Test]
    public function it_can_be_constructed_directly(): void
    {
        $now = new \DateTimeImmutable();
        $signal = new RequestSignal('GET', 'https://x.com', 'x', '1.2.3.4', [], [], 0, $now);

        $this->assertSame('GET', $signal->method);
        $this->assertSame('https://x.com', $signal->url);
        $this->assertSame($now, $signal->capturedAt);
    }
}
