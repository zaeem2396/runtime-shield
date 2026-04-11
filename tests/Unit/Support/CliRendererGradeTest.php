<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Support\CliRenderer;

final class CliRendererGradeTest extends TestCase
{
    #[Test]
    public function grade_color_returns_cyan_for_B(): void
    {
        $this->assertSame('cyan', CliRenderer::gradeColor('B'));
    }

    #[Test]
    public function grade_color_returns_yellow_for_C(): void
    {
        $this->assertSame('yellow', CliRenderer::gradeColor('C'));
    }

    #[Test]
    public function grade_color_returns_red_for_D(): void
    {
        $this->assertSame('red', CliRenderer::gradeColor('D'));
    }

    #[Test]
    public function risk_label_safe_is_green(): void
    {
        $this->assertStringContainsString('green', CliRenderer::riskLabel('SAFE'));
    }

    #[Test]
    public function risk_label_critical_is_red(): void
    {
        $this->assertStringContainsString('red', CliRenderer::riskLabel('CRITICAL'));
    }

    #[Test]
    public function divider_has_correct_character_count(): void
    {
        $raw = strip_tags(CliRenderer::divider(20));

        $this->assertStringContainsString('─', $raw);
    }
}
