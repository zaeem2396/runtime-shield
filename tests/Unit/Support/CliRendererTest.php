<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\Support\CliRenderer;

final class CliRendererTest extends TestCase
{
    #[Test]
    public function severity_icon_returns_distinct_emoji_per_level(): void
    {
        $icons = [
            CliRenderer::severityIcon(Severity::CRITICAL),
            CliRenderer::severityIcon(Severity::HIGH),
            CliRenderer::severityIcon(Severity::MEDIUM),
            CliRenderer::severityIcon(Severity::LOW),
            CliRenderer::severityIcon(Severity::INFO),
        ];

        $this->assertCount(5, array_unique($icons));
    }

    #[Test]
    public function badge_contains_severity_label(): void
    {
        $badge = CliRenderer::badge(Severity::CRITICAL);

        $this->assertStringContainsString('CRITICAL', $badge);
    }

    #[Test]
    public function badge_contains_ansi_color_tag(): void
    {
        $badge = CliRenderer::badge(Severity::HIGH);

        $this->assertStringContainsString('<fg=', $badge);
    }

    #[Test]
    public function grade_color_returns_green_for_A(): void
    {
        $this->assertSame('green', CliRenderer::gradeColor('A'));
    }

    #[Test]
    public function grade_color_returns_red_for_F(): void
    {
        $this->assertSame('red', CliRenderer::gradeColor('F'));
    }

    #[Test]
    public function divider_returns_box_drawing_string(): void
    {
        $divider = CliRenderer::divider(10);

        $this->assertStringContainsString('─', $divider);
    }

    #[Test]
    public function risk_label_wraps_in_colored_tag(): void
    {
        $this->assertStringContainsString('CRITICAL', CliRenderer::riskLabel('CRITICAL'));
        $this->assertStringContainsString('SAFE', CliRenderer::riskLabel('SAFE'));
    }

    #[Test]
    public function checkmark_returns_different_strings_for_true_and_false(): void
    {
        $this->assertNotSame(CliRenderer::checkmark(true), CliRenderer::checkmark(false));
        $this->assertStringContainsString('green', CliRenderer::checkmark(true));
        $this->assertStringContainsString('red', CliRenderer::checkmark(false));
    }
}
