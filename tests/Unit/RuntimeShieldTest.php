<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\RuntimeShield;

final class RuntimeShieldTest extends TestCase
{
    #[Test]
    public function it_reports_version(): void
    {
        $this->assertSame('0.1.0', RuntimeShield::version());
    }
}
