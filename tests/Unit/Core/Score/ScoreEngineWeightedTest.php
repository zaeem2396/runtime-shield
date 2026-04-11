<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\Core\Score\ScoreEngine;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class ScoreEngineWeightedTest extends TestCase
{
    public function test_equal_weights_produce_simple_average(): void
    {
        // All weights equal (20 each) → simple average of category scores
        // AUTH = 80 (1 CRITICAL → -20), all others = 100
        // Expected overall = (80*20 + 100*20 + 100*20 + 100*20 + 100*20) / 100 = 96
        $engine = $this->engine(20, 20, 20, 20, 20);

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::CRITICAL),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(96, $score->overall);
    }

    public function test_custom_weights_change_overall_score(): void
    {
        // AUTH weight = 100, all others 0 → overall == auth category score
        $engine = $this->engine(100, 0, 0, 0, 0);

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::CRITICAL),
        ]);

        $score = $engine->calculate($violations);

        // AUTH score = 80 (1 CRITICAL = -20)
        $this->assertSame(80, $score->overall);
    }

    public function test_weight_for_unaffected_category_does_not_lower_score(): void
    {
        // CSRF weight = 100, rest = 0; no CSRF violations → CSRF = 100 → overall = 100
        $engine = $this->engine(0, 100, 0, 0, 0);

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::CRITICAL),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(100, $score->overall);
    }

    public function test_overall_is_rounded_correctly(): void
    {
        // AUTH = 50 (2 CRITICAL → -40? no: 5 CRITICAL → -100 → 0, or 2 HIGH → -20 → 80)
        // Let's use 3 MEDIUM auth violations → 100 - 3*5 = 85
        // AUTH weight = 30, rest = 70 (25+20+15+10=70) at 100 each
        // Overall = (85*30 + 100*70) / 100 = 2550/100 + 7000/100 = 95.5 → 96
        $engine = new ScoreEngine(new RuleCategoryMap());

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::MEDIUM),
            $this->violation('public-route-without-auth', Severity::MEDIUM),
            $this->violation('public-route-without-auth', Severity::MEDIUM),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(96, $score->overall);
    }

    public function test_total_violations_reflects_all_violations(): void
    {
        $engine = new ScoreEngine(new RuleCategoryMap());

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('missing-csrf-protection', Severity::HIGH),
            $this->violation('missing-rate-limit', Severity::MEDIUM),
        ]);

        $score = $engine->calculate($violations);

        $this->assertSame(3, $score->totalViolations);
    }

    public function test_categories_count_equals_number_of_score_categories(): void
    {
        $engine = new ScoreEngine(new RuleCategoryMap());
        $score = $engine->calculate(new ViolationCollection());

        $this->assertCount(5, $score->categories);
    }
    private function engine(int $auth, int $csrf, int $rl, int $val, int $fu): ScoreEngine
    {
        return new ScoreEngine(new RuleCategoryMap(), [
            'auth' => $auth,
            'csrf' => $csrf,
            'rate_limit' => $rl,
            'validation' => $val,
            'file_upload' => $fu,
        ]);
    }

    private function violation(string $ruleId, Severity $severity): Violation
    {
        return new Violation($ruleId, 'T', 'D', $severity);
    }
}
