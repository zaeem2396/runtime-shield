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
        return $this->config->isEnabled();
    }

    public function version(): string
    {
        return PackageVersion::VERSION;
    }
}
