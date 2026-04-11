<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\DTO\Score\ScoreCategory;

final class RuleCategoryMapRulesForTest extends TestCase
{
    private RuleCategoryMap $map;

    protected function setUp(): void
    {
        $this->map = new RuleCategoryMap();
    }

    public function test_rules_for_auth_returns_auth_rule(): void
    {
        $rules = $this->map->rulesFor(ScoreCategory::AUTH);
        $this->assertContains('public-route-without-auth', $rules);
        $this->assertCount(1, $rules);
    }

    public function test_rules_for_csrf_returns_csrf_rule(): void
    {
        $rules = $this->map->rulesFor(ScoreCategory::CSRF);
        $this->assertContains('missing-csrf-protection', $rules);
    }

    public function test_rules_for_rate_limit_returns_rate_limit_rule(): void
    {
        $rules = $this->map->rulesFor(ScoreCategory::RATE_LIMIT);
        $this->assertContains('missing-rate-limit', $rules);
    }

    public function test_rules_for_validation_returns_validation_rule(): void
    {
        $rules = $this->map->rulesFor(ScoreCategory::VALIDATION);
        $this->assertContains('missing-validation', $rules);
    }

    public function test_rules_for_file_upload_returns_file_upload_rule(): void
    {
        $rules = $this->map->rulesFor(ScoreCategory::FILE_UPLOAD);
        $this->assertContains('file-upload-without-validation', $rules);
    }

    public function test_total_rules_for_all_categories_equals_mapping_count(): void
    {
        $total = 0;

        foreach (ScoreCategory::cases() as $category) {
            $total += count($this->map->rulesFor($category));
        }

        $this->assertSame(count($this->map->allMappings()), $total);
    }
}
