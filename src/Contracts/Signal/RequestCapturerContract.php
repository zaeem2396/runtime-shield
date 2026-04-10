<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use Illuminate\Http\Request;
use RuntimeShield\DTO\Signal\RequestSignal;

/**
 * Laravel adapter contract — converts an Illuminate Request to a RequestSignal.
 * Stateless: the same instance can be reused across multiple requests.
 */
interface RequestCapturerContract
{
    public function capture(Request $request): RequestSignal;
}
