<?php

declare(strict_types=1);

namespace Tests\Unit\DTO\Score;

use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Score\ScoreCategory;

final class ScoreCategoryTest extends TestCase
{
    public function test_all_cases_have_unique_values(): void
    {
        $values = array_map(static fn (ScoreCategory $c): string => $c->value, ScoreCategory::cases());
        $this->assertCount(count(ScoreCategory::cases()), array_unique($values));
    }

    public function test_label_returns_non_empty_string_for_all_cases(): void
    {
        foreach (ScoreCategory::cases() as $category) {
            $this->assertNotEmpty($category->label(), "label() is empty for {$category->value}");
        }
    }

    public function test_description_returns_non_empty_string_for_all_cases(): void
    {
        foreach (ScoreCategory::cases() as $category) {
            $this->assertNotEmpty($category->description(), "description() is empty for {$category->value}");
        }
    }

    public function test_default_weights_sum_to_100(): void
    {
        $total = array_sum(
            array_map(static fn (ScoreCategory $c): int => $c->defaultWeight(), ScoreCategory::cases()),
        );

        $this->assertSame(100, $total);
    }

    public function test_auth_has_highest_default_weight(): void
    {
        $this->assertGreaterThan(
            ScoreCategory::CSRF->defaultWeight(),
            ScoreCategory::AUTH->defaultWeight(),
        );
    }

    /**
     * @dataProvider categoryLabelProvider
     */
    public function test_expected_labels(ScoreCategory $category, string $expectedFragment): void
    {
        $this->assertStringContainsStringIgnoringCase($expectedFragment, $category->label());
    }

    /**
     * @return array<string, array{ScoreCategory, string}>
     */
    public static function categoryLabelProvider(): array
    {
        return [
            'auth'        => [ScoreCategory::AUTH, 'Auth'],
            'csrf'        => [ScoreCategory::CSRF, 'CSRF'],
            'rate_limit'  => [ScoreCategory::RATE_LIMIT, 'Rate'],
            'validation'  => [ScoreCategory::VALIDATION, 'Validation'],
            'file_upload' => [ScoreCategory::FILE_UPLOAD, 'File'],
        ];
    }
}
