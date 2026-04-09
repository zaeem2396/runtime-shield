<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel\Signal;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\Laravel\Signal\RequestCapturer;

final class RequestCapturerTest extends TestCase
{
    private RequestCapturer $capturer;

    protected function setUp(): void
    {
        $this->capturer = new RequestCapturer();
    }

    #[Test]
    public function it_captures_the_http_method(): void
    {
        $request = Request::create('/test', 'POST');
        $signal  = $this->capturer->capture($request);

        $this->assertSame('POST', $signal->method);
    }

    #[Test]
    public function it_captures_the_request_path(): void
    {
        $request = Request::create('/api/users', 'GET');
        $signal  = $this->capturer->capture($request);

        $this->assertSame('api/users', $signal->path);
    }

    #[Test]
    public function it_captures_query_parameters(): void
    {
        $request = Request::create('/search', 'GET', ['q' => 'hello', 'page' => '2']);
        $signal  = $this->capturer->capture($request);

        $this->assertArrayHasKey('q', $signal->query);
        $this->assertSame('hello', $signal->query['q']);
    }

    #[Test]
    public function it_captures_body_size_from_raw_content(): void
    {
        $body    = '{"name":"Alice"}';
        $request = Request::create('/users', 'POST', [], [], [], [], $body);
        $signal  = $this->capturer->capture($request);

        $this->assertSame(strlen($body), $signal->bodySize);
    }

    #[Test]
    public function it_returns_a_request_signal_instance(): void
    {
        $request = Request::create('/ping', 'GET');
        $signal  = $this->capturer->capture($request);

        $this->assertInstanceOf(RequestSignal::class, $signal);
    }

    #[Test]
    public function it_normalizes_headers_to_flat_strings(): void
    {
        $request = Request::create('/api', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $signal  = $this->capturer->capture($request);

        $this->assertIsArray($signal->headers);
        $this->assertArrayHasKey('accept', $signal->headers);
        $this->assertIsString($signal->headers['accept']);
    }
}
