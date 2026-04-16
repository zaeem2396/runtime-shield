<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Core\Performance\MetricsStore;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Support\CliRenderer;

/**
 * Optional developer dashboard: runtime config summary, registered rules, and
 * recent middleware performance samples from the in-memory metrics ring buffer.
 *
 * Usage: php artisan runtime-shield:dashboard
 */
final class DashboardCommand extends Command
{
    protected $signature = 'runtime-shield:dashboard
                            {--format=table : Output format (table|json)}
                            {--samples= : Override recent middleware sample rows (default from config)}';

    protected $description = 'Show a local debug dashboard (config, rules, recent middleware metrics)';

    public function __construct(
        private readonly MetricsStore $metricsStore,
        private readonly RuleRegistry $ruleRegistry,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! (bool) config('runtime_shield.dx.dashboard.enabled', true)) {
            $this->components->warn('RuntimeShield dashboard is disabled (runtime_shield.dx.dashboard.enabled).');

            return self::SUCCESS;
        }

        if ($this->option('format') === 'json') {
            $this->line((string) json_encode($this->payload(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->renderTableView();

        return self::SUCCESS;
    }

    private function renderTableView(): void
    {
        $recentLimit = $this->resolveRecentMetricsLimit();

        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Debug Dashboard</>');
        $this->line(CliRenderer::divider(52));
        $this->line('');

        $enabled = (bool) config('runtime_shield.enabled', true);
        $sampling = $this->floatConfig('runtime_shield.sampling_rate', 1.0);
        $async = (bool) config('runtime_shield.performance.async', false);

        $this->line(sprintf('  Shield enabled:     <options=bold>%s</>', $enabled ? 'yes' : 'no'));
        $this->line(sprintf('  Sampling rate:      <options=bold>%s</>', $this->formatFloat($sampling)));
        $this->line(sprintf('  Async rule engine:  <options=bold>%s</>', $async ? 'yes' : 'no'));
        $this->line(sprintf('  Registered rules:   <options=bold>%d</>', $this->ruleRegistry->count()));
        $this->line('');

        $stats = $this->metricsStore->toArray();
        $this->line('<options=bold>  Middleware metrics (ring buffer)</>');
        $this->line(sprintf(
            '    Samples: %d   Avg: %s ms   Max: %s ms   Min: %s ms   Sampled %%: %s',
            $this->intFromMixed($stats['count'] ?? 0),
            $this->stringFromMixed($stats['avg_ms'] ?? 0),
            $this->stringFromMixed($stats['max_ms'] ?? 0),
            $this->stringFromMixed($stats['min_ms'] ?? 0),
            $this->stringFromMixed($stats['sampling_rate'] ?? 0),
        ));
        $this->line('');

        if ($recentLimit === 0) {
            return;
        }

        $rows = [];
        $all = $this->metricsStore->all();
        $slice = array_slice($all, -$recentLimit);

        foreach ($slice as $m) {
            $rows[] = [
                $m->capturedAt->format('H:i:s'),
                $m->formattedMs(),
                (string) $m->rulesEvaluated,
                $m->wasSampled ? 'yes' : 'no',
                (string) $m->memoryDeltaKb,
            ];
        }

        if ($rows === []) {
            $this->line('  <fg=gray>No middleware samples recorded yet (traffic must hit RuntimeShieldMiddleware).</>');

            return;
        }

        $this->line(sprintf('<options=bold>  Last %d sample(s)</>', count($rows)));
        $this->table(['Time', 'Processing', 'Rules', 'Sampled', 'Δ mem KB'], $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $recentLimit = $this->resolveRecentMetricsLimit();
        $all = $this->metricsStore->all();
        $slice = array_slice($all, -$recentLimit);
        $recent = [];

        foreach ($slice as $m) {
            $recent[] = $m->toArray();
        }

        return [
            'shield_enabled' => (bool) config('runtime_shield.enabled', true),
            'sampling_rate' => $this->floatConfig('runtime_shield.sampling_rate', 1.0),
            'async_rule_engine' => (bool) config('runtime_shield.performance.async', false),
            'registered_rules' => $this->ruleRegistry->count(),
            'metrics_summary' => $this->metricsStore->toArray(),
            'recent_middleware_metrics' => $recent,
        ];
    }

    private function formatFloat(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4f', $value), '0'), '.') ?: '0';
    }

    private function resolveRecentMetricsLimit(): int
    {
        $opt = $this->option('samples');

        if ($opt !== null && $opt !== '' && is_numeric($opt)) {
            return max(0, (int) $opt);
        }

        return max(0, $this->intConfig('runtime_shield.dx.dashboard.recent_metrics', 8));
    }

    private function floatConfig(string $key, float $default): float
    {
        $v = config($key, $default);

        if (is_float($v) || is_int($v)) {
            return (float) $v;
        }

        if (is_numeric($v)) {
            return (float) $v;
        }

        return $default;
    }

    private function intConfig(string $key, int $default): int
    {
        $v = config($key, $default);

        if (is_int($v)) {
            return $v;
        }

        if (is_numeric($v)) {
            return (int) $v;
        }

        return $default;
    }

    private function intFromMixed(mixed $v): int
    {
        if (is_int($v)) {
            return $v;
        }

        if (is_numeric($v)) {
            return (int) $v;
        }

        return 0;
    }

    private function stringFromMixed(mixed $v): string
    {
        if (is_string($v)) {
            return $v;
        }

        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        return '0';
    }
}
