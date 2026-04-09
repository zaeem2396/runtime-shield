<?php

declare(strict_types=1);

namespace RuntimeShield\Core;

use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\DTO\RuntimeShieldConfig;

final class ConfigRepository implements ConfigRepositoryContract
{
    private readonly RuntimeShieldConfig $config;

    /** @param array<string, mixed> $raw */
    public function __construct(array $raw = [])
    {
        $this->config = RuntimeShieldConfig::fromArray($raw);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'enabled'      => $this->config->enabled,
            'sampling_rate' => $this->config->samplingRate,
            'rules'        => $this->config->rules,
            'performance'  => $this->config->performance,
            default        => $default,
        };
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return [
            'enabled'      => $this->config->enabled,
            'sampling_rate' => $this->config->samplingRate,
            'rules'        => $this->config->rules,
            'performance'  => $this->config->performance,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->config->enabled;
    }

    public function samplingRate(): float
    {
        return $this->config->samplingRate;
    }

    /** Expose the underlying DTO for callers that need the full value object. */
    public function dto(): RuntimeShieldConfig
    {
        return $this->config;
    }
}
