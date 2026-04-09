<?php

declare(strict_types=1);

namespace RuntimeShield\DTO;

/**
 * Immutable value object representing the resolved package configuration.
 * Created once at boot and shared read-only across the request lifecycle.
 */
final class RuntimeShieldConfig
{
    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $performance
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly float $samplingRate,
        public readonly array $rules,
        public readonly array $performance,
    ) {
    }

    /**
     * Construct from a raw configuration array (e.g. from Laravel config()).
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $rawEnabled = $config['enabled'] ?? true;
        $rawRate = $config['sampling_rate'] ?? 1.0;
        $rawRules = $config['rules'] ?? [];
        $rawPerf = $config['performance'] ?? [];

        return new self(
            enabled: (bool) $rawEnabled,
            samplingRate: is_numeric($rawRate) ? (float) $rawRate : 1.0,
            rules: is_array($rawRules) ? $rawRules : [],
            performance: is_array($rawPerf) ? $rawPerf : [],
        );
    }

    /**
     * Return a new instance with the enabled flag overridden.
     * Preserves immutability — original is never mutated.
     */
    public function withEnabled(bool $enabled): self
    {
        return new self(
            enabled: $enabled,
            samplingRate: $this->samplingRate,
            rules: $this->rules,
            performance: $this->performance,
        );
    }

    /**
     * Return a new instance with the sampling rate overridden.
     */
    public function withSamplingRate(float $samplingRate): self
    {
        return new self(
            enabled: $this->enabled,
            samplingRate: $samplingRate,
            rules: $this->rules,
            performance: $this->performance,
        );
    }
}
