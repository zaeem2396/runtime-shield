<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

interface EngineContract
{
    /**
     * Boot the engine for the current request lifecycle.
     * No-ops immediately when the shield is disabled.
     */
    public function boot(): void;

    /** Whether the engine has been booted in the current lifecycle. */
    public function isBooted(): bool;
}
