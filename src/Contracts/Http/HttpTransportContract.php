<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Http;

use RuntimeShield\DTO\Http\HttpResponse;

interface HttpTransportContract
{
    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, array $headers, string $body, int $timeoutMs): HttpResponse;
}
