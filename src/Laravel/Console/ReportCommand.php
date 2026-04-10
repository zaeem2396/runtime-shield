<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Contracts\Report\ReportBuilderContract;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that generates a comprehensive security report for all
 * registered application routes.
 *
 * Usage: php artisan runtime-shield:report
 */
final class ReportCommand extends Command
{
    protected $signature = 'runtime-shield:report
                            {--format=table : Output format (table|json)}
                            {--save= : Optional file path to write JSON report output}';

    protected $description = 'Generate a full security report for all registered routes';

    public function __construct(private readonly ReportBuilderContract $builder)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Security Report</>');
        $this->line(CliRenderer::divider(52));

        $report = $this->builder->build();

        $this->line("  Scanning <options=bold>{$report->routeCount}</> route(s)…");
        $this->line("  Generated: <fg=gray>{$report->scannedAt->format('Y-m-d H:i:s')}</>");
        $this->line('');

        $json = (string) json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($this->option('format') === 'json') {
            $this->line($json);

            return self::SUCCESS;
        }

        $savePath = $this->option('save');

        if (is_string($savePath) && $savePath !== '') {
            file_put_contents($savePath, $json);
            $this->line("  <fg=green>✔ Report saved to {$savePath}</>");
            $this->line('');
        }

        if ($report->violations->isEmpty()) {
            $this->line('<fg=green>  ✔ No security violations detected.</>');
            $this->renderSummary($report);

            return self::SUCCESS;
        }

        $this->renderViolationGroups($report);
        $this->renderSummary($report);

        $hasCritical = count($report->violations->critical()) > 0;
        $hasHigh     = count($report->violations->high()) > 0;

        return ($hasCritical || $hasHigh) ? self::FAILURE : self::SUCCESS;
    }

    private function renderViolationGroups(SecurityReport $report): void
    {
        $groups = [
            Severity::CRITICAL,
            Severity::HIGH,
            Severity::MEDIUM,
            Severity::LOW,
        ];

        foreach ($groups as $severity) {
            $items = $report->violations->bySeverity($severity);

            if ($items === []) {
                continue;
            }

            $icon  = CliRenderer::severityIcon($severity);
            $badge = CliRenderer::badge($severity);
            $count = count($items);

            $this->line("{$icon} {$badge} — {$count} violation(s)");
            $this->line(CliRenderer::divider(52));

            foreach ($items as $violation) {
                $route = $violation->route !== '' ? "<fg=white>{$violation->route}</>" : '';
                $this->line("  {$route}");
                $this->line("  <fg=gray>↳ {$violation->title}</>");
                $this->line('');
            }
        }
    }

    private function renderSummary(SecurityReport $report): void
    {
        $score        = $report->score();
        $grade        = $report->grade();
        $gradeColor   = CliRenderer::gradeColor($grade);
        $exposedCount = $report->exposedRouteCount();

        $this->line(CliRenderer::divider(52));
        $this->line("  Security Score: <fg={$gradeColor};options=bold>{$score}/100</>   Grade: <fg={$gradeColor};options=bold>{$grade}</>");
        $this->line("  Routes: <options=bold>{$report->routeCount}</>  Exposed: <fg=red>{$exposedCount}</>");
        $this->line(sprintf(
            '  Violations: <options=bold>%d</>  (<fg=red>%d critical</> · <fg=yellow>%d high</> · <fg=cyan>%d medium</> · <fg=blue>%d low</>)',
            $report->violations->count(),
            count($report->violations->critical()),
            count($report->violations->high()),
            count($report->violations->medium()),
            count($report->violations->low()),
        ));
        $this->line(CliRenderer::divider(52));
        $this->line('');
    }
}
