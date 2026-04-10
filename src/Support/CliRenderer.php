<?php

declare(strict_types=1);

namespace RuntimeShield\Support;

use RuntimeShield\DTO\Rule\Severity;

/**
 * Stateless helper for building styled CLI output strings.
 *
 * All methods are pure (no I/O); callers pass the returned strings to
 * $this->line() or similar console methods. This makes the renderer fully
 * unit-testable without a running Artisan application.
 */
final class CliRenderer
{
    /**
     * Emoji icon representing the given severity level.
     */
    public static function severityIcon(Severity $severity): string
    {
        return match ($severity) {
            Severity::CRITICAL => '🔴',
            Severity::HIGH     => '🟡',
            Severity::MEDIUM   => '🔵',
            Severity::LOW      => '⚪',
            Severity::INFO     => '💬',
        };
    }

    /**
     * ANSI-coloured bold badge for a severity level, e.g. " CRITICAL ".
     */
    public static function badge(Severity $severity): string
    {
        $color = $severity->color();
        $label = $severity->label();

        return "<fg={$color};options=bold>{$label}</>";
    }

    /**
     * ANSI color for a letter grade (A–F).
     */
    public static function gradeColor(string $grade): string
    {
        return match ($grade) {
            'A'     => 'green',
            'B'     => 'cyan',
            'C'     => 'yellow',
            'D', 'F' => 'red',
            default => 'white',
        };
    }

    /**
     * Horizontal rule of box-drawing characters.
     */
    public static function divider(int $width = 50): string
    {
        return '<fg=gray>' . str_repeat('─', $width) . '</>';
    }

    /**
     * Coloured risk label for a route — used by the routes table.
     */
    public static function riskLabel(string $label): string
    {
        $color = match ($label) {
            'CRITICAL'    => 'red',
            'HIGH RISK'   => 'yellow',
            'MEDIUM RISK' => 'cyan',
            'LOW RISK'    => 'blue',
            'SAFE'        => 'green',
            default       => 'white',
        };

        return "<fg={$color};options=bold>{$label}</>";
    }

    /**
     * Unicode check / cross symbol with ANSI colour.
     */
    public static function checkmark(bool $value): string
    {
        return $value ? '<fg=green>✔</>' : '<fg=red>✘</>';
    }
}
