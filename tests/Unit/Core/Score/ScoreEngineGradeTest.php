<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\Core\Score\ScoreEngine;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Validates grade assignments at boundary values using equal-weight engines
 * so that the overall score equals the average of all category scores.
 */
final class ScoreEngineGradeTest extends TestCase
{
    public function test_grade_a_at_exactly_90(): void
    {
        // AUTH = 50 (5 * -10 HIGH), rest = 100
        // overall = (50*20 + 100*80) / 100 = (1000 + 8000)/100 = 90
        $engine = $this->equalWeightEngine();

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(90, $score->overall);
        $this->assertSame('A', $score->grade);
    }

    public function test_grade_b_at_75_to_89(): void
    {
        // AUTH = 60 (4 HIGH = -40), rest = 100
        // overall = (60*20 + 100*80)/100 = 92 — that's still A
        // Need more categories to bring it down; use AUTH + CSRF
        // AUTH = 60 (4 HIGH), CSRF = 60 (4 HIGH), rest = 100
        // overall = (60+60+100+100+100)*20/100 = 424*20/100? no
        // = (60*20 + 60*20 + 100*20 + 100*20 + 100*20)/100 = (1200+1200+2000+2000+2000)/100 = 84
        $engine = $this->equalWeightEngine();

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(84, $score->overall);
        $this->assertSame('B', $score->grade);
    }

    public function test_grade_c_at_60_to_74(): void
    {
        // AUTH=60, CSRF=60, RL=60 (4 HIGH each), VAL=100, FU=100
        // overall = (60+60+60+100+100)*20/100 = (3*60 + 2*100)*20/100 = 380*20/100 = 76? no
        // = (60*20 + 60*20 + 60*20 + 100*20 + 100*20)/100 = (1200+1200+1200+2000+2000)/100 = 76 → B
        // Need 4 failing to land in C: AUTH=60, CSRF=60, RL=60, VAL=60, FU=100
        // = (60+60+60+60+100)*20/100 = 340*20/100 = 68 → C
        $engine = $this->equalWeightEngine();

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-rate-limit', Severity::HIGH),
            $this->violation('missing-rate-limit', Severity::HIGH),
            $this->violation('missing-rate-limit', Severity::HIGH),
            $this->violation('missing-rate-limit', Severity::HIGH),
            $this->violation('missing-validation', Severity::HIGH),
            $this->violation('missing-validation', Severity::HIGH),
            $this->violation('missing-validation', Severity::HIGH),
            $this->violation('missing-validation', Severity::HIGH),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(68, $score->overall);
        $this->assertSame('C', $score->grade);
    }

    public function test_grade_d_at_40_to_59(): void
    {
        // All categories at 0 except FU (1 violation, HIGH → 90)
        // AUTH=0, CSRF=0, RL=0, VAL=0, FU=90
        // overall = (0+0+0+0+90)*20/100 = 18 → F
        // Need to land in D (40-59): e.g. all five at 40 → 40 → D
        // To get 40: 6 HIGH each = -60 → score=40
        $engine = $this->equalWeightEngine();

        $violations = new ViolationCollection([
            ...$this->many('public-route-without-auth', Severity::HIGH, 6),
            ...$this->many('missing-csrf-protection', Severity::HIGH, 6),
            ...$this->many('missing-rate-limit', Severity::HIGH, 6),
            ...$this->many('missing-validation', Severity::HIGH, 6),
            ...$this->many('file-upload-without-validation', Severity::HIGH, 6),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(40, $score->overall);
        $this->assertSame('D', $score->grade);
    }
    /**
     * Engine with equal weights so overall ≈ simple average of all category scores.
     */
    private function equalWeightEngine(): ScoreEngine
    {
        return new ScoreEngine(new RuleCategoryMap(), [
            'auth' => 20,
            'csrf' => 20,
            'rate_limit' => 20,
            'validation' => 20,
            'file_upload' => 20,
        ]);
    }

    private function violation(string $ruleId, Severity $severity): Violation
    {
        return new Violation($ruleId, 'T', 'D', $severity);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @return list<Violation>
     */
    private function many(string $ruleId, Severity $severity, int $n): array
    {
        return array_fill(0, $n, $this->violation($ruleId, $severity));
    }
}
