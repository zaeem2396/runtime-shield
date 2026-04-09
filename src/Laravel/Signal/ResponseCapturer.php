<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Signal;

use RuntimeShield\Contracts\Signal\ResponseCapturerContract;
use RuntimeShield\DTO\Signal\ResponseSignal;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts a Symfony/Laravel Response into an immutable ResponseSignal.
 * Stateless — safe to register as a container singleton.
 */
final class ResponseCapturer implements ResponseCapturerContract
{
    public function capture(Response $response, float $startTimeMs): ResponseSignal
    {
        $content = $response->getContent();

        return new ResponseSignal(
            statusCode: $response->getStatusCode(),
            statusText: Response::$statusTexts[$response->getStatusCode()] ?? '',
            headers: $this->normalizeHeaders($response),
            bodySize: strlen($content !== false ? $content : ''),
            responseTimeMs: max(0.0, round((microtime(true) * 1000.0) - $startTimeMs, 2)),
            capturedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHeaders(Response $response): array
    {
        $normalized = [];

        foreach ($response->headers->all() as $name => $values) {
            $normalized[$name] = implode(', ', $values);
        }

        return $normalized;
    }
}
