<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Score;

use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Score\SecurityScore;

/**
 * Contract for the Security Score Engine.
 *
 * The engine takes a ViolationCollection, groups violations by category, and
 * produces a weighted SecurityScore with per-category breakdowns.
 */
interface ScoreEngineContract
{
    /**
     * Calculate a detailed SecurityScore from the given violations.
     */
    public function calculate(ViolationCollection $violations): SecurityScore;
}
