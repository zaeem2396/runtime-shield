<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use RuntimeShield\DTO\Signal\ResponseSignal;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel adapter contract — converts a Symfony Response to a ResponseSignal.
 * startTimeMs is the microtime(true)*1000 value captured at request ingress.
 */
interface ResponseCapturerContract
{
    public function capture(Response $response, float $startTimeMs): ResponseSignal;
}
