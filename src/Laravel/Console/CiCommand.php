<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\Laravel\Support\ApplicationRouteScanner;

/**
 * Non-interactive CI gate: fails when the security score or severity budgets
 * configured via flags or `runtime_shield.dx.ci` are exceeded.
 *
 * Usage: php artisan runtime-shield:ci
 */
final class CiCommand extends Command
{
    protected $signature = 'runtime-shield:ci
                            {--min-score= : Minimum overall security score (0–100)}
                            {--max-critical= : Maximum allowed critical violations}
                            {--max-high= : Maximum allowed high-severity violations; omit to skip unless configured}';

    protected $description = 'Fail with a non-zero exit code when security score or severity gates are not met';

    public function __construct(
        private readonly ApplicationRouteScanner $routeScanner,
        private readonly ScoreEngineContract $scoreEngine,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $violations = $this->routeScanner->scanRoutes();
        $score = $this->scoreEngine->calculate($violations);

        $minScore = $this->resolveMinScore();
        $maxCritical = $this->resolveMaxCritical();
        $maxHigh = $this->resolveMaxHigh();

        $failed = false;

        if ($score->overall < $minScore) {
            $this->error(sprintf(
                'Security score %d is below the required minimum of %d.',
                $score->overall,
                $minScore,
            ));
            $failed = true;
        }

        $criticalCount = count($violations->critical());

        if ($criticalCount > $maxCritical) {
            $this->error(sprintf(
                'Critical violations: %d exceeds the allowed maximum of %d.',
                $criticalCount,
                $maxCritical,
            ));
            $failed = true;
        }

        if ($maxHigh !== null) {
            $highCount = count($violations->high());

            if ($highCount > $maxHigh) {
                $this->error(sprintf(
                    'High-severity violations: %d exceeds the allowed maximum of %d.',
                    $highCount,
                    $maxHigh,
                ));
                $failed = true;
            }
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info(sprintf(
            'RuntimeShield CI gate passed (score %d/100, critical %d, high %d).',
            $score->overall,
            $criticalCount,
            count($violations->high()),
        ));

        return self::SUCCESS;
    }

    private function resolveMinScore(): int
    {
        $opt = $this->option('min-score');

        if ($opt !== null && $opt !== '' && is_numeric($opt)) {
            return (int) $opt;
        }

        $fromDx = config('runtime_shield.dx.ci.min_score');

        if ($fromDx !== null && $fromDx !== '' && is_numeric($fromDx)) {
            return (int) $fromDx;
        }

        return (int) config('runtime_shield.scoring.thresholds.pass', 75);
    }

    private function resolveMaxCritical(): int
    {
        $opt = $this->option('max-critical');

        if ($opt !== null && $opt !== '' && is_numeric($opt)) {
            return (int) $opt;
        }

        return (int) config('runtime_shield.dx.ci.max_critical_violations', 0);
    }

    private function resolveMaxHigh(): int|null
    {
        $opt = $this->option('max-high');

        if ($opt !== null && $opt !== '') {
            return is_numeric($opt) ? (int) $opt : null;
        }

        $fromDx = config('runtime_shield.dx.ci.max_high_violations');

        if ($fromDx === null || $fromDx === '') {
            return null;
        }

        return is_numeric($fromDx) ? (int) $fromDx : null;
    }
}
