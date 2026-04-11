<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\Support\CliRenderer;

final class CliRendererSeverityIconTest extends TestCase
{
    #[Test]
    public function critical_severity_icon_is_red_circle(): void
    {
        $this->assertSame('🔴', CliRenderer::severityIcon(Severity::CRITICAL));
    }

    #[Test]
    public function high_severity_icon_is_yellow_circle(): void
    {
        $this->assertSame('🟡', CliRenderer::severityIcon(Severity::HIGH));
    }

    #[Test]
    public function medium_severity_icon_is_blue_circle(): void
    {
        $this->assertSame('🔵', CliRenderer::severityIcon(Severity::MEDIUM));
    }

    #[Test]
    public function low_severity_icon_is_white_circle(): void
    {
        $this->assertSame('⚪', CliRenderer::severityIcon(Severity::LOW));
    }

    #[Test]
    public function info_severity_icon_is_speech_bubble(): void
    {
        $this->assertSame('💬', CliRenderer::severityIcon(Severity::INFO));
    }

    #[Test]
    public function badge_for_critical_contains_red_color(): void
    {
        $badge = CliRenderer::badge(Severity::CRITICAL);

        $this->assertStringContainsString('red', $badge);
    }
}
