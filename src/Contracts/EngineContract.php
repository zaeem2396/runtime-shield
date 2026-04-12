<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

interface EngineContract
{
    /**
     * Boot the engine for the current request lifecycle.
     * No-ops immediately when the shield is disabled.
     */
    public function boot(): void;

    /** Whether the engine has been booted in the current lifecycle. */
    public function isBooted(): bool;

    /**
     * Run all registered rules against the given context and return every
     * violation found. This is the primary entry-point for on-demand rule
     * evaluation available to the middleware and alert subsystem.
     */
    public function evaluate(SecurityRuntimeContext $context): ViolationCollection;
}
