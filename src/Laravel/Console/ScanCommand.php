<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeShield\Contracts\Advisory\ViolationAdvisoryEnricherContract;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\Core\Advisory\AdvisoryBatchProgress;
use RuntimeShield\Core\Advisory\OpenAiViolationAdvisoryEnricher;
use RuntimeShield\DTO\Advisory\AdvisorySource;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\Laravel\Support\ApplicationRouteScanner;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that scans all registered routes for security issues
 * using the RuntimeShield Rule Engine.
 *
 * Usage: php artisan runtime-shield:scan
 */
final class ScanCommand extends Command
{
    protected $signature = 'runtime-shield:scan
                            {--format=table : Output format (table|json)}
                            {--score : Show the weighted security score after scanning}
                            {--no-ai : Skip AI advisory enrichment for this run}';

    protected $description = 'Scan all registered routes for security violations';

    public function __construct(
        private readonly ApplicationRouteScanner $routeScanner,
        private readonly ScoreEngineContract $scoreEngine,
        private readonly ViolationAdvisoryEnricherContract $advisoryEnricher,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routeCount = $this->routeScanner->scannableRouteCount();
        $violations = $this->routeScanner->scanRoutes();

        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Security Scan</>');
        $this->line('<fg=gray>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $this->line('  Scanning <options=bold>' . $routeCount . '</> route(s)…');
        $this->line('');

        if (! (bool) $this->option('no-ai')) {
            $progress = null;
            $progressBar = null;

            if (! $violations->isEmpty() && $this->advisoryEnricher instanceof OpenAiViolationAdvisoryEnricher) {
                $this->line('  <fg=yellow>AI advisory: calling OpenAI in batches (can take several minutes). Use --no-ai to skip.</>');
                $batchSizeConfig = config('runtime_shield.ai.batch_size', 20);
                $batchSize = is_int($batchSizeConfig) ? $batchSizeConfig : (is_numeric($batchSizeConfig) ? (int) $batchSizeConfig : 20);
                $batchSize = max(1, $batchSize);
                $totalBatches = (int) max(1, (int) ceil($violations->count() / $batchSize));
                $progressBar = $this->output->createProgressBar($totalBatches);
                $progressBar->setFormat('  AI advisory %current%/%max% [%bar%] %percent:3s%%');
                $progressBar->start();
                $progress = $this->laravel->make(AdvisoryBatchProgress::class);
                $progress->setListener(function (int $_current, int $_total, int $_inBatch) use ($progressBar): void {
                    $progressBar->advance();
                });
            }

            try {
                $violations = $this->advisoryEnricher->enrich($violations, AdvisorySource::Cli);
            } finally {
                $progress?->clear();
                if ($progressBar !== null) {
                    $progressBar->finish();
                    $this->line('');
                }
            }
        }

        if ($violations->isEmpty()) {
            $this->line('<fg=green>  ✔ No security violations detected.</>');
            $this->line('');

            return self::SUCCESS;
        }

        $sorted = $violations->sorted();

        $format = $this->option('format');

        if ($format === 'json') {
            $this->renderJson($violations);
        } else {
            $this->renderTable($sorted);
        }

        $criticalCount = count($violations->critical());
        $highCount = count($violations->high());

        $this->line('');
        $this->line(sprintf(
            '  Found <options=bold>%d</> violation(s)  '
            . '(<fg=red>%d critical</> · <fg=yellow>%d high</> · <fg=cyan>%d medium</> · <fg=blue>%d low</>)',
            $violations->count(),
            $criticalCount,
            $highCount,
            count($violations->medium()),
            count($violations->low()),
        ));

        if ($this->option('score')) {
            $score = $this->scoreEngine->calculate($violations);
            $color = CliRenderer::scoreColor($score->overall);
            $gradeColor = CliRenderer::gradeColor($score->grade);

            $this->line('');
            $this->line(sprintf(
                '  Security Score: <fg=%s;options=bold>%d/100</>   Grade: <fg=%s;options=bold>%s</>',
                $color,
                $score->overall,
                $gradeColor,
                $score->grade,
            ));
        }

        $this->line('');

        return ($criticalCount > 0 || $highCount > 0) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Render violations as a JSON document to stdout.
     */
    private function renderJson(ViolationCollection $collection): void
    {
        $this->line((string) json_encode(
            array_map(static fn (Violation $v): array => $v->toArray(), $collection->all()),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));
    }

    /**
     * Render the violation table, grouped and sorted by severity (worst first).
     *
     * @param list<Violation> $violations
     */
    private function renderTable(array $violations): void
    {
        $rows = [];

        foreach ($violations as $violation) {
            $severity = $violation->severity;
            $label = "<fg={$severity->color()}>{$severity->label()}</>";

            $ruleCell = $violation->title;

            if ($violation->advisory !== null) {
                $ruleCell .= "\n<fg=gray>" . Str::limit($violation->advisory->summary, 72) . '</>';
            }

            $rows[] = [
                $violation->route !== '' ? $violation->route : '—',
                $ruleCell,
                $label,
            ];
        }

        $this->table(['Route / URI', 'Rule', 'Severity'], $rows);
    }
}
