<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\Core\Score\ScoreEngine;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Score\ScoreCategory;

final class ScoreEngineTest extends TestCase
{
    private ScoreEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ScoreEngine(new RuleCategoryMap());
    }

    private function violation(string $ruleId, Severity $severity): Violation
    {
        return new Violation($ruleId, 'Test', 'Desc', $severity);
    }

    private function collection(Violation ...$violations): ViolationCollection
    {
        $col = new ViolationCollection();

        foreach ($violations as $v) {
            $col = new ViolationCollection(array_merge($col->all(), [$v]));
        }

        return $col;
    }

    // ── Empty violations ─────────────────────────────────────────────────────

    public function test_empty_violations_produces_overall_100(): void
    {
        $score = $this->engine->calculate(new ViolationCollection());

        $this->assertSame(100, $score->overall);
    }

    public function test_empty_violations_produces_grade_a(): void
    {
        $score = $this->engine->calculate(new ViolationCollection());

        $this->assertSame('A', $score->grade);
    }

    public function test_empty_violations_all_categories_score_100(): void
    {
        $score = $this->engine->calculate(new ViolationCollection());

        foreach ($score->categories as $cs) {
            $this->assertSame(100, $cs->score, "Category {$cs->category->value} should score 100");
        }
    }

    public function test_empty_violations_total_violations_is_zero(): void
    {
        $score = $this->engine->calculate(new ViolationCollection());

        $this->assertSame(0, $score->totalViolations);
    }

    // ── Single violation ─────────────────────────────────────────────────────

    public function test_single_critical_auth_violation_deducts_20_from_auth(): void
    {
        $violations = $this->collection($this->violation('public-route-without-auth', Severity::CRITICAL));
        $score      = $this->engine->calculate($violations);

        $auth = $score->categoryScore(ScoreCategory::AUTH);
        $this->assertNotNull($auth);
        $this->assertSame(80, $auth->score);
    }

    public function test_single_high_csrf_violation_deducts_10_from_csrf(): void
    {
        $violations = $this->collection($this->violation('missing-csrf-protection', Severity::HIGH));
        $score      = $this->engine->calculate($violations);

        $csrf = $score->categoryScore(ScoreCategory::CSRF);
        $this->assertNotNull($csrf);
        $this->assertSame(90, $csrf->score);
    }

    public function test_single_medium_rate_limit_violation_deducts_5(): void
    {
        $violations = $this->collection($this->violation('missing-rate-limit', Severity::MEDIUM));
        $score      = $this->engine->calculate($violations);

        $rl = $score->categoryScore(ScoreCategory::RATE_LIMIT);
        $this->assertNotNull($rl);
        $this->assertSame(95, $rl->score);
    }

    public function test_single_low_validation_violation_deducts_2(): void
    {
        $violations = $this->collection($this->violation('missing-validation', Severity::LOW));
        $score      = $this->engine->calculate($violations);

        $v = $score->categoryScore(ScoreCategory::VALIDATION);
        $this->assertNotNull($v);
        $this->assertSame(98, $v->score);
    }

    public function test_info_violation_deducts_nothing(): void
    {
        $violations = $this->collection($this->violation('missing-validation', Severity::INFO));
        $score      = $this->engine->calculate($violations);

        $v = $score->categoryScore(ScoreCategory::VALIDATION);
        $this->assertNotNull($v);
        $this->assertSame(100, $v->score);
    }

    // ── Score floor ──────────────────────────────────────────────────────────

    public function test_category_score_does_not_go_below_zero(): void
    {
        $violations = $this->collection(
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('public-route-without-auth', Severity::CRITICAL),
        );

        $score = $this->engine->calculate($violations);
        $auth  = $score->categoryScore(ScoreCategory::AUTH);
        $this->assertNotNull($auth);
        $this->assertSame(0, $auth->score);
    }

    // ── Violation grouping ───────────────────────────────────────────────────

    public function test_violations_grouped_by_category(): void
    {
        $violations = $this->collection(
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('missing-csrf-protection', Severity::HIGH),
        );

        $score = $this->engine->calculate($violations);

        $auth = $score->categoryScore(ScoreCategory::AUTH);
        $csrf = $score->categoryScore(ScoreCategory::CSRF);

        $this->assertNotNull($auth);
        $this->assertNotNull($csrf);

        $this->assertSame(1, $auth->violationCount);
        $this->assertSame(1, $csrf->violationCount);
    }

    public function test_unrecognised_rule_id_does_not_affect_any_category(): void
    {
        $v = new Violation('unknown-custom-rule', 'Test', 'Desc', Severity::HIGH);

        $violations = $this->collection($v);
        $score      = $this->engine->calculate($violations);

        foreach ($score->categories as $cs) {
            $this->assertSame(100, $cs->score, "Category {$cs->category->value} should be unaffected");
        }
    }

    // ── Grade boundaries ─────────────────────────────────────────────────────

    public function test_grade_a_at_90(): void
    {
        // AUTH only — score 80 (1 CRITICAL = -20), all others 100
        // weighted: (80*30 + 100*25 + 100*20 + 100*15 + 100*10) / 100 = 94
        $violations = $this->collection($this->violation('public-route-without-auth', Severity::CRITICAL));
        $score      = $this->engine->calculate($violations);

        $this->assertSame('A', $score->grade);
    }

    public function test_grade_f_when_all_categories_score_zero(): void
    {
        $engine = new ScoreEngine(new RuleCategoryMap(), [
            'auth'        => 20,
            'csrf'        => 20,
            'rate_limit'  => 20,
            'validation'  => 20,
            'file_upload' => 20,
        ]);

        $violations = $this->collection(
            ...$this->manyViolations('public-route-without-auth', Severity::CRITICAL, 6),
            ...$this->manyViolations('missing-csrf-protection', Severity::CRITICAL, 6),
            ...$this->manyViolations('missing-rate-limit', Severity::CRITICAL, 6),
            ...$this->manyViolations('missing-validation', Severity::CRITICAL, 6),
            ...$this->manyViolations('file-upload-without-validation', Severity::CRITICAL, 6),
        );

        $score = $engine->calculate($violations);

        $this->assertSame(0, $score->overall);
        $this->assertSame('F', $score->grade);
    }

    // ── Summarise ────────────────────────────────────────────────────────────

    public function test_summarise_returns_expected_keys(): void
    {
        $score   = $this->engine->calculate(new ViolationCollection());
        $summary = $this->engine->summarise($score);

        $this->assertArrayHasKey('overall', $summary);
        $this->assertArrayHasKey('grade', $summary);
        $this->assertArrayHasKey('passed', $summary);
        $this->assertArrayHasKey('failed', $summary);
        $this->assertArrayHasKey('total', $summary);
    }

    public function test_summarise_all_passed_when_no_violations(): void
    {
        $score   = $this->engine->calculate(new ViolationCollection());
        $summary = $this->engine->summarise($score);

        $this->assertSame(5, $summary['passed']);
        $this->assertSame(0, $summary['failed']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @return list<Violation>
     */
    private function manyViolations(string $ruleId, Severity $severity, int $count): array
    {
        return array_fill(0, $count, $this->violation($ruleId, $severity));
    }
}
