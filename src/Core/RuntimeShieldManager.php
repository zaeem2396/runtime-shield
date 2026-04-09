<?php

declare(strict_types=1);

namespace RuntimeShield\Core;

use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\Contracts\ShieldContract;
use RuntimeShield\Support\PackageVersion;

final class RuntimeShieldManager implements ShieldContract
{
    public function __construct(
        private readonly ConfigRepositoryContract $config,
    ) {}

    public function isEnabled(): bool
    {
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

        return (mt_rand() / mt_getrandmax()) <= $rate;
    }

    public function version(): string
    {
        return PackageVersion::VERSION;
    }
}
