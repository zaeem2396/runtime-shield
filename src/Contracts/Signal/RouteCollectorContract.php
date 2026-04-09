<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use Illuminate\Http\Request;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Laravel adapter contract — extracts route metadata from a resolved Request.
 * Returns null when no route has been matched (e.g. 404 responses).
 */
interface RouteCollectorContract
{
    public function collect(Request $request): RouteSignal|null;
}
