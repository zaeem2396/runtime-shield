<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\Core\Score\ScoreEngine;
use RuntimeShield\DTO\Rule\Severity;

final class ScoreEngineDeductionTest extends TestCase
{
    private ScoreEngine $engine;

    public function test_deduction_for_critical_is_20(): void
    {
        $this->assertSame(20, $this->engine->deductionFor(Severity::CRITICAL));
    }

    public function test_deduction_for_high_is_10(): void
    {
        $this->assertSame(10, $this->engine->deductionFor(Severity::HIGH));
    }

    public function test_deduction_for_medium_is_5(): void
    {
        $this->assertSame(5, $this->engine->deductionFor(Severity::MEDIUM));
    }

    public function test_deduction_for_low_is_2(): void
    {
        $this->assertSame(2, $this->engine->deductionFor(Severity::LOW));
    }

    public function test_deduction_for_info_is_0(): void
    {
        $this->assertSame(0, $this->engine->deductionFor(Severity::INFO));
    }

    protected function setUp(): void
    {
        $this->engine = new ScoreEngine(new RuleCategoryMap());
    }
}
