<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\DTO\Score\ScoreCategory;

final class RuleCategoryMapTest extends TestCase
{
    private RuleCategoryMap $map;

    protected function setUp(): void
    {
        $this->map = new RuleCategoryMap();
    }

    public function test_public_route_without_auth_maps_to_auth(): void
    {
        $this->assertSame(ScoreCategory::AUTH, $this->map->categoryFor('public-route-without-auth'));
    }

    public function test_missing_csrf_protection_maps_to_csrf(): void
    {
        $this->assertSame(ScoreCategory::CSRF, $this->map->categoryFor('missing-csrf-protection'));
    }

    public function test_missing_rate_limit_maps_to_rate_limit(): void
    {
        $this->assertSame(ScoreCategory::RATE_LIMIT, $this->map->categoryFor('missing-rate-limit'));
    }

    public function test_missing_validation_maps_to_validation(): void
    {
        $this->assertSame(ScoreCategory::VALIDATION, $this->map->categoryFor('missing-validation'));
    }

    public function test_file_upload_without_validation_maps_to_file_upload(): void
    {
        $this->assertSame(ScoreCategory::FILE_UPLOAD, $this->map->categoryFor('file-upload-without-validation'));
    }

    public function test_unknown_rule_id_returns_null(): void
    {
        $this->assertNull($this->map->categoryFor('completely-unknown-rule'));
    }

    public function test_all_mappings_covers_all_built_in_rules(): void
    {
        $mappings = $this->map->allMappings();

        $this->assertArrayHasKey('public-route-without-auth', $mappings);
        $this->assertArrayHasKey('missing-csrf-protection', $mappings);
        $this->assertArrayHasKey('missing-rate-limit', $mappings);
        $this->assertArrayHasKey('missing-validation', $mappings);
        $this->assertArrayHasKey('file-upload-without-validation', $mappings);
    }

    public function test_all_mappings_count_matches_built_in_rules(): void
    {
        $this->assertCount(5, $this->map->allMappings());
    }

    public function test_all_mappings_values_are_score_category_instances(): void
    {
        foreach ($this->map->allMappings() as $category) {
            $this->assertInstanceOf(ScoreCategory::class, $category);
        }
    }

    public function test_all_five_categories_are_covered(): void
    {
        $categories = array_unique(
            array_map(
                static fn (ScoreCategory $c): string => $c->value,
                array_values($this->map->allMappings()),
            ),
        );

        $this->assertCount(5, $categories);
    }
}
