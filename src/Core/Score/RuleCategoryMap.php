<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Score;

use RuntimeShield\Contracts\Score\RuleCategoryMapContract;
use RuntimeShield\DTO\Score\ScoreCategory;

/**
 * Static mapping from built-in rule IDs to their ScoreCategory.
 *
 * Custom rule authors can extend this class or bind their own
 * RuleCategoryMapContract implementation in the service container.
 */
final class RuleCategoryMap implements RuleCategoryMapContract
{
    /** @var array<string, ScoreCategory> */
    private const MAP = [
        'public-route-without-auth'    => ScoreCategory::AUTH,
        'missing-csrf-protection'      => ScoreCategory::CSRF,
        'missing-rate-limit'           => ScoreCategory::RATE_LIMIT,
        'missing-validation'           => ScoreCategory::VALIDATION,
        'file-upload-without-validation' => ScoreCategory::FILE_UPLOAD,
    ];

    public function categoryFor(string $ruleId): ScoreCategory|null
    {
        return self::MAP[$ruleId] ?? null;
    }

    /**
     * @return array<string, ScoreCategory>
     */
    public function allMappings(): array
    {
        return self::MAP;
    }

    /**
     * All rule IDs that map to the given category.
     *
     * @return list<string>
     */
    public function rulesFor(ScoreCategory $category): array
    {
        $ruleIds = [];

        foreach (self::MAP as $ruleId => $mappedCategory) {
            if ($mappedCategory === $category) {
                $ruleIds[] = $ruleId;
            }
        }

        return $ruleIds;
    }
}
