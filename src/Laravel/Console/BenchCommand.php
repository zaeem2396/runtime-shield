<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Core\Performance\BatchedRuleEngine;
use RuntimeShield\Core\Performance\PerformanceTimer;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that measures rule-engine evaluation time across all
 * registered routes and reports timing statistics.
 *
 * Injects BatchedRuleEngine directly (not RuleEngineContract) so that
 * benchmarks always run synchronous rule evaluation regardless of whether
 * the async flag is enabled — measuring real rule cost, not queue dispatch.
 *
 * Usage: php artisan runtime-shield:bench
 */
final class BenchCommand extends Command
{
    protected $signature = 'runtime-shield:bench
                            {--iterations=1 : Number of evaluation passes per route}
                            {--format=table  : Output format (table|json)}';

    protected $description = 'Benchmark rule evaluation time across all registered routes';

    public function __construct(
        private readonly Router $router,
        private readonly BatchedRuleEngine $ruleEngine,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routes = $this->collectRoutes();
        $iterations = max(1, (int) $this->option('iterations'));

        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Benchmark</>');
        $this->line(CliRenderer::divider(52));
        $this->line('  Routes: <options=bold>' . count($routes) . "</>   Iterations per route: <options=bold>{$iterations}</>");
        $this->line('');

        $results = $this->runBenchmark($routes, $iterations);

        if ($this->option('format') === 'json') {
            $this->renderJson($results);

            return self::SUCCESS;
        }

        $this->renderTable($results);
        $this->renderSummary($results);

        return self::SUCCESS;
    }

    // ── Benchmark logic ───────────────────────────────────────────────────

    /**
     * @param list<Route> $routes
     *
     * @return list<array{uri: string, method: string, avg_ms: float, min_ms: float, max_ms: float, violations: int}>
     */
    private function runBenchmark(array $routes, int $iterations): array
    {
        $results = [];

        foreach ($routes as $route) {
            $context = $this->buildContext($route);
            $timings = [];
            $violations = 0;

            for ($i = 0; $i < $iterations; $i++) {
                $measured = PerformanceTimer::measure(fn () => $this->ruleEngine->run($context));
                $timings[] = $measured['elapsed_ms'];
                $violations = $measured['result']->count();
            }

            $methods = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
            $method = $methods[0] ?? 'GET';

            $results[] = [
                'uri' => $route->uri(),
                'method' => $method,
                'avg_ms' => count($timings) > 0 ? array_sum($timings) / count($timings) : 0.0,
                'min_ms' => count($timings) > 0 ? min($timings) : 0.0,
                'max_ms' => count($timings) > 0 ? max($timings) : 0.0,
                'violations' => $violations,
            ];
        }

        return $results;
    }

    // ── Route collection ─────────────────────────────────────────────────

    /**
     * @return list<Route>
     */
    private function collectRoutes(): array
    {
        $skipPrefixes = ['_ignition', '_telescope', 'horizon/', 'telescope/', 'debugbar/'];
        $routes = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $skip = false;

            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    $skip = true;

                    break;
                }
            }

            if (! $skip) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    private function buildContext(Route $route): SecurityRuntimeContext
    {
        $methods = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
        $method = $methods[0] ?? 'GET';

        /** @var list<string> $middleware */
        $middleware = array_values(
            array_filter($route->gatherMiddleware(), static fn (mixed $v): bool => is_string($v)),
        );

        $routeSignal = new RouteSignal(
            name: (string) ($route->getName() ?? ''),
            uri: $route->uri(),
            action: $route->getActionName(),
            controller: (string) ($route->getControllerClass() ?? ''),
            middleware: $middleware,
            hasNamedRoute: $route->getName() !== null,
        );

        $requestSignal = new RequestSignal(
            method: $method,
            url: 'http://localhost/' . ltrim($route->uri(), '/'),
            path: '/' . ltrim($route->uri(), '/'),
            ip: '127.0.0.1',
            headers: [],
            query: [],
            bodySize: 0,
            capturedAt: new \DateTimeImmutable(),
        );

        return (new RuntimeContextBuilder())
            ->withRoute($routeSignal)
            ->withRequest($requestSignal)
            ->build();
    }

    // ── Rendering ────────────────────────────────────────────────────────

    /**
     * @param list<array{uri: string, method: string, avg_ms: float, min_ms: float, max_ms: float, violations: int}> $results
     */
    private function renderTable(array $results): void
    {
        $rows = [];

        foreach ($results as $r) {
            $avgColor = match (true) {
                $r['avg_ms'] < 1.0 => 'green',
                $r['avg_ms'] < 5.0 => 'yellow',
                default => 'red',
            };

            $rows[] = [
                strtoupper($r['method']),
                $r['uri'],
                sprintf('<fg=%s>%.3f ms</>', $avgColor, $r['avg_ms']),
                sprintf('%.3f ms', $r['min_ms']),
                sprintf('%.3f ms', $r['max_ms']),
                (string) $r['violations'],
            ];
        }

        $this->table(
            ['Method', 'Route', 'Avg', 'Min', 'Max', 'Violations'],
            $rows,
        );
    }

    /**
     * @param list<array{uri: string, method: string, avg_ms: float, min_ms: float, max_ms: float, violations: int}> $results
     */
    private function renderSummary(array $results): void
    {
        if ($results === []) {
            return;
        }

        $avgMs = array_sum(array_column($results, 'avg_ms')) / count($results);
        $maxMs = max(array_column($results, 'max_ms'));
        $totalV = array_sum(array_column($results, 'violations'));

        $this->line(CliRenderer::divider(52));
        $this->line(sprintf(
            '  Routes: <options=bold>%d</>   Avg: <options=bold>%.3f ms</>   Max: <options=bold>%.3f ms</>   Violations: <options=bold>%d</>',
            count($results),
            $avgMs,
            $maxMs,
            $totalV,
        ));
        $this->line(CliRenderer::divider(52));
        $this->line('');
    }

    /**
     * @param list<array{uri: string, method: string, avg_ms: float, min_ms: float, max_ms: float, violations: int}> $results
     */
    private function renderJson(array $results): void
    {
        $this->line((string) json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
