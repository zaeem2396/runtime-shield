<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Rule;

/**
 * Security violation severity levels — ordered from most to least critical.
 */
enum Severity: string
{
    case CRITICAL = 'critical';
    case HIGH     = 'high';
    case MEDIUM   = 'medium';
    case LOW      = 'low';
    case INFO     = 'info';

    /** Human-readable uppercase label. */
    public function label(): string
    {
        return strtoupper($this->value);
    }

    /** ANSI color tag for CLI output. */
    public function color(): string
    {
        return match ($this) {
            self::CRITICAL => 'red',
            self::HIGH     => 'yellow',
            self::MEDIUM   => 'cyan',
            self::LOW      => 'blue',
            self::INFO     => 'white',
        };
    }

    /**
     * Sort priority — lower number means higher priority.
     * Useful for sorting violations before display.
     */
    public function priority(): int
    {
        return match ($this) {
            self::CRITICAL => 0,
            self::HIGH     => 1,
            self::MEDIUM   => 2,
            self::LOW      => 3,
            self::INFO     => 4,
        };
    }
}
