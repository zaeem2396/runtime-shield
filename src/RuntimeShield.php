<?php

declare(strict_types=1);

namespace RuntimeShield;

use RuntimeShield\Support\PackageVersion;

/**
 * Package façade / entry point.
 *
 * Provides a convenient static surface for library consumers who
 * do not use the Laravel container. In a Laravel application prefer
 * resolving ShieldContract or RuntimeShieldManager from the container.
 */
final class RuntimeShield
{
    /** Return the installed package version string (e.g. "0.1.0"). */
    public static function version(): string
    {
        return PackageVersion::VERSION;
    }

    /**
     * Compare the installed version against a given semver string.
     * Returns -1, 0, or 1 (same semantics as version_compare()).
     */
    public static function versionCompareTo(string $other): int
    {
        return PackageVersion::compareTo($other);
    }
}
