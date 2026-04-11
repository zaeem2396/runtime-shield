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

final class ScoreEngineConfigWeightTest extends TestCase
{
    public function test_default_weights_match_score_category_defaults(): void
    {
        $engine = new ScoreEngine(new RuleCategoryMap());
        $score = $engine->calculate(new ViolationCollection());

        foreach ($score->categories as $cs) {
            $this->assertSame(
                $cs->category->defaultWeight(),
                $cs->weight,
                "Weight mismatch for {$cs->category->value}",
            );
        }
    }

    public function test_partial_weight_override_uses_default_for_missing_keys(): void
    {
        // Override only AUTH weight; others should fall back to defaultWeight()
        $engine = new ScoreEngine(new RuleCategoryMap(), ['auth' => 50]);
        $score = $engine->calculate(new ViolationCollection());

        $auth = $score->categoryScore(ScoreCategory::AUTH);
        $csrf = $score->categoryScore(ScoreCategory::CSRF);

        $this->assertNotNull($auth);
        $this->assertNotNull($csrf);

        $this->assertSame(50, $auth->weight);
        $this->assertSame(ScoreCategory::CSRF->defaultWeight(), $csrf->weight);
    }

    public function test_category_violation_count_reflects_grouped_count(): void
    {
        $engine = new ScoreEngine(new RuleCategoryMap());

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::CRITICAL),
            $this->violation('public-route-without-auth', Severity::HIGH),
            $this->violation('public-route-without-auth', Severity::MEDIUM),
        ]);

        $score = $engine->calculate($violations);
        $auth = $score->categoryScore(ScoreCategory::AUTH);

        $this->assertNotNull($auth);
        $this->assertSame(3, $auth->violationCount);
    }

    public function test_other_categories_unaffected_when_only_auth_violated(): void
    {
        $engine = new ScoreEngine(new RuleCategoryMap());

        $violations = new ViolationCollection([
            $this->violation('public-route-without-auth', Severity::CRITICAL),
        ]);

        $score = $engine->calculate($violations);

        foreach ([ScoreCategory::CSRF, ScoreCategory::RATE_LIMIT, ScoreCategory::VALIDATION, ScoreCategory::FILE_UPLOAD] as $category) {
            $cs = $score->categoryScore($category);
            $this->assertNotNull($cs);
            $this->assertSame(100, $cs->score, "{$category->value} should be unaffected");
            $this->assertSame(0, $cs->violationCount);
        }
    }
    private function violation(string $ruleId, Severity $severity): Violation
    {
        return new Violation($ruleId, 'T', 'D', $severity);
    }
}
