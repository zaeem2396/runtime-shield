<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Http;

use RuntimeShield\Contracts\Http\HttpTransportContract;
use RuntimeShield\DTO\Http\HttpResponse;

/**
 * POST helper using PHP streams (no extra Composer dependencies).
 */
final class StreamHttpTransport implements HttpTransportContract
{
    public function post(string $url, array $headers, string $body, int $timeoutMs): HttpResponse
    {
        $timeoutSeconds = max(1, (int) ceil($timeoutMs / 1000));

        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => $body,
                'timeout' => $timeoutSeconds,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $stream = @fopen($url, 'r', false, $context);

        if ($stream === false) {
            return new HttpResponse(0, '');
        }

        $result = stream_get_contents($stream);
        if ($result === false) {
            fclose($stream);

            return new HttpResponse(0, '');
        }

        $meta = stream_get_meta_data($stream);
        fclose($stream);

        $status = 0;
        $wrapperData = $meta['wrapper_data'] ?? null;

        if (is_array($wrapperData) && isset($wrapperData[0]) && is_string($wrapperData[0])) {
            if (preg_match('#HTTP/\d\.\d\s+(\d+)#', $wrapperData[0], $m) === 1) {
                $status = (int) $m[1];
            }
        }

        return new HttpResponse($status, $result);
    }
}
