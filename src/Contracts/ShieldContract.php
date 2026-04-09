<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

interface ShieldContract
{
    /**
     * Determine whether the shield is currently active.
     * When false, all processing must be skipped for zero overhead.
     */
    public function isEnabled(): bool;

    /** Return the installed package version string (e.g. "0.1.0"). */
    public function version(): string;
}
