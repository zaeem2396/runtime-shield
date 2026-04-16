<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Contracts\Report\ReportBuilderContract;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\Laravel\Support\ApplicationRouteScanner;
use RuntimeShield\Support\JsonExportEnvelope;

/**
 * Write machine-readable JSON artifacts (score snapshot or full report) for
 * dashboards, archives, or CI artifacts.
 *
 * Usage: php artisan runtime-shield:export score
 */
final class ExportCommand extends Command
{
    protected $signature = 'runtime-shield:export
                            {artifact=score : score|report}
                            {--output= : Write JSON to this file path instead of stdout}';

    protected $description = 'Export a JSON security score or report artifact';

    public function __construct(
        private readonly ApplicationRouteScanner $routeScanner,
        private readonly ReportBuilderContract $reportBuilder,
        private readonly ScoreEngineContract $scoreEngine,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $rawArtifact = $this->argument('artifact');
        $artifact = strtolower(trim(is_string($rawArtifact) ? $rawArtifact : ''));

        if ($artifact === 'score') {
            $payload = $this->buildScorePayload();
        } elseif ($artifact === 'report') {
            $payload = $this->buildReportPayload();
        } else {
            $this->error('Artifact must be "score" or "report".');

            return self::INVALID;
        }

        $json = (string) json_encode(
            JsonExportEnvelope::wrap($artifact, $payload),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );

        $path = $this->option('output');

        if (is_string($path) && $path !== '') {
            if (@file_put_contents($path, $json) === false) {
                $this->error('Could not write output file.');

                return self::FAILURE;
            }

            $this->info('Wrote export to ' . $path);

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildScorePayload(): array
    {
        $violations = $this->routeScanner->scanRoutes();
        $score = $this->scoreEngine->calculate($violations);

        return [
            'security_score' => $score->toArray(),
            'violations_summary' => [
                'total' => $violations->count(),
                'critical' => count($violations->critical()),
                'high' => count($violations->high()),
                'medium' => count($violations->medium()),
                'low' => count($violations->low()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportPayload(): array
    {
        $report = $this->reportBuilder->build();
        $score = $this->scoreEngine->calculate($report->violations);

        return [
            'report' => $report->toArray(),
            'security_score' => $score->toArray(),
        ];
    }
}
