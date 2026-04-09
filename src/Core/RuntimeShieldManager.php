<?php

declare(strict_types=1);

namespace RuntimeShield\Core;

use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\Contracts\ShieldContract;
use RuntimeShield\Support\PackageVersion;

final class RuntimeShieldManager implements ShieldContract
{
    private bool $forcedDisabled = false;

    public function __construct(
        private readonly ConfigRepositoryContract $config,
    ) {
    }

    /**
     * Programmatically disable the shield regardless of config.
     * Useful in tests or when the application explicitly opts out at runtime.
     */
    public function disable(): void
    {
        $this->forcedDisabled = true;
    }

    /**
     * Undo a programmatic disable, restoring config-driven evaluation.
     * Has no effect if the shield was never force-disabled.
     */
    public function enable(): void
    {
        $this->forcedDisabled = false;
    }

    public function isEnabled(): bool
    {
        if ($this->forcedDisabled) {
            return false;
        }

        if (! $this->config->isEnabled()) {
            return false;
        }

        $rate = $this->config->samplingRate();

        if ($rate <= 0.0) {
            return false;
        }

        if ($rate >= 1.0) {
            return true;
        }

        return (mt_rand() / getrandmax()) <= $rate;
    }

    public function version(): string
    {
        return PackageVersion::VERSION;
    }
}
