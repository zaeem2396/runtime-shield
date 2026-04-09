<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

interface ConfigRepositoryContract
{
    /**
     * Retrieve a configuration value by dot-notation key.
     *
     * @param mixed $default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Return all configuration values as an associative array.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /** Whether the shield is enabled in configuration. */
    public function isEnabled(): bool;

    /** The configured sampling rate (0.0–1.0). */
    public function samplingRate(): float;
}
