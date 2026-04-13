<?php

declare(strict_types=1);

namespace RuntimeShield\Support;

final class PackageVersion
{
    public const VERSION = '1.0.0';

    public const MAJOR = 1;

    public const MINOR = 0;

    public const PATCH = 0;

    /** Formatted version string. */
    public static function string(): string
    {
        return self::VERSION;
    }

    /**
     * Version as [major, minor, patch] tuple.
     *
     * @return list<int>
     */
    public static function parts(): array
    {
        return [self::MAJOR, self::MINOR, self::PATCH];
    }

    /** Compare this version against a semver string. Returns -1, 0, or 1. */
    public static function compareTo(string $other): int
    {
        return version_compare(self::VERSION, $other);
    }
}
