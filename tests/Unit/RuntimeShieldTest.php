<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\RuntimeShield;
use RuntimeShield\Support\PackageVersion;

final class RuntimeShieldTest extends TestCase
{
    #[Test]
    public function it_reports_version_matching_package_version_constant(): void
    {
        $this->assertSame(PackageVersion::VERSION, RuntimeShield::version());
    }

    #[Test]
    public function version_compare_to_same_returns_zero(): void
    {
        $this->assertSame(0, RuntimeShield::versionCompareTo(PackageVersion::VERSION));
    }

    #[Test]
    public function version_compare_to_older_returns_one(): void
    {
        $this->assertSame(1, RuntimeShield::versionCompareTo('0.0.1'));
    }
}
