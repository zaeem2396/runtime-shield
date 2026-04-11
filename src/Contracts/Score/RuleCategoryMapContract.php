<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Score;

use RuntimeShield\DTO\Score\ScoreCategory;

/**
 * Maps rule IDs to their corresponding ScoreCategory.
 *
 * Implementations allow the ScoreEngine to attribute each violation to the
 * correct scoring bucket without hard-coding rule IDs in engine logic.
 */
interface RuleCategoryMapContract
{
    /**
     * Return the ScoreCategory for the given rule ID, or null if not mapped.
     */
    public function categoryFor(string $ruleId): ScoreCategory|null;

    /**
     * Return all registered rule-ID → ScoreCategory mappings.
     *
     * @return array<string, ScoreCategory>
     */
    public function allMappings(): array;

    /**
     * Return all rule IDs that belong to the given category.
     *
     * @return list<string>
     */
    public function rulesFor(ScoreCategory $category): array;
}
