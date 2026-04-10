<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use RuntimeShield\DTO\Signal\AuthSignal;

/**
 * Adapter contract — inspects the current authentication state and
 * returns an immutable AuthSignal snapshot. Always returns a value;
 * unauthenticated state is expressed via AuthSignal::unauthenticated().
 */
interface AuthCollectorContract
{
    public function collect(): AuthSignal;
}
