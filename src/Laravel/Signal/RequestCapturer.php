<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Signal;

use DateTimeImmutable;
use Illuminate\Http\Request;
use RuntimeShield\Contracts\Signal\RequestCapturerContract;
use RuntimeShield\DTO\Signal\RequestSignal;

/**
 * Converts an Illuminate HTTP Request into an immutable RequestSignal.
 * Stateless — safe to register as a container singleton.
 */
final class RequestCapturer implements RequestCapturerContract
{
    public function capture(Request $request): RequestSignal
    {
        return new RequestSignal(
            method: $request->method(),
            url: $request->fullUrl(),
            path: $request->path(),
            ip: $request->ip() ?? '',
            headers: $this->normalizeHeaders($request),
            query: $request->query->all(),
            bodySize: strlen($request->getContent()),
            capturedAt: new DateTimeImmutable(),
        );
    }

    /**
     * Flatten multi-value header bags to single strings joined by ", ".
     *
     * @return array<string, string>
     */
    private function normalizeHeaders(Request $request): array
    {
        $normalized = [];

        foreach ($request->headers->all() as $name => $values) {
            $normalized[$name] = implode(', ', $values);
        }

        return $normalized;
    }
}
