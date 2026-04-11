<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Support\CliRenderer;

final class CliRendererProgressBarTest extends TestCase
{
    public function test_progress_bar_for_score_100_is_fully_green(): void
    {
        $bar = CliRenderer::progressBar(100);
        $this->assertStringContainsString('green', $bar);
        $this->assertStringNotContainsString('░', $bar);
    }

    public function test_progress_bar_for_score_0_has_no_filled_blocks(): void
    {
        $bar = CliRenderer::progressBar(0);
        $this->assertStringNotContainsString('█', $bar);
    }

    public function test_progress_bar_for_score_50_is_yellow(): void
    {
        $bar = CliRenderer::progressBar(50);
        $this->assertStringContainsString('yellow', $bar);
    }

    public function test_progress_bar_for_score_74_is_yellow(): void
    {
        $bar = CliRenderer::progressBar(74);
        $this->assertStringContainsString('yellow', $bar);
    }

    public function test_progress_bar_for_score_75_is_green(): void
    {
        $bar = CliRenderer::progressBar(75);
        $this->assertStringContainsString('green', $bar);
    }

    public function test_progress_bar_for_score_49_is_red(): void
    {
        $bar = CliRenderer::progressBar(49);
        $this->assertStringContainsString('red', $bar);
    }

    public function test_progress_bar_default_width_is_20_characters(): void
    {
        // Strip ANSI tags and count visual chars (█ + ░)
        $bar = CliRenderer::progressBar(50);
        $cleaned = preg_replace('/<[^>]+>/', '', $bar) ?? '';
        $this->assertSame(20, mb_strlen($cleaned));
    }

    public function test_progress_bar_custom_width_is_respected(): void
    {
        $bar = CliRenderer::progressBar(50, 10);
        $cleaned = preg_replace('/<[^>]+>/', '', $bar) ?? '';
        $this->assertSame(10, mb_strlen($cleaned));
    }

    public function test_progress_bar_clamps_score_above_100(): void
    {
        $bar = CliRenderer::progressBar(150);
        $cleaned = preg_replace('/<[^>]+>/', '', $bar) ?? '';
        $this->assertSame(20, mb_strlen($cleaned));
    }

    public function test_progress_bar_clamps_score_below_0(): void
    {
        $bar = CliRenderer::progressBar(-10);
        $cleaned = preg_replace('/<[^>]+>/', '', $bar) ?? '';
        $this->assertSame(20, mb_strlen($cleaned));
    }

    public function test_score_color_green_for_75_and_above(): void
    {
        $this->assertSame('green', CliRenderer::scoreColor(75));
        $this->assertSame('green', CliRenderer::scoreColor(100));
    }

    public function test_score_color_yellow_for_50_to_74(): void
    {
        $this->assertSame('yellow', CliRenderer::scoreColor(50));
        $this->assertSame('yellow', CliRenderer::scoreColor(74));
    }

    public function test_score_color_red_below_50(): void
    {
        $this->assertSame('red', CliRenderer::scoreColor(49));
        $this->assertSame('red', CliRenderer::scoreColor(0));
    }
}
