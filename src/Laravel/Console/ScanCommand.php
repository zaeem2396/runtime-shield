<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Artisan command that scans all registered routes for security issues
 * using the RuntimeShield Rule Engine.
 *
 * Usage: php artisan runtime-shield:scan
 */
final class ScanCommand extends Command
{
    protected $signature = 'runtime-shield:scan
                            {--format=table : Output format (table|json)}';

    protected $description = 'Scan all registered routes for security violations';

    public function __construct(
        private readonly Router $router,
        private readonly RuleEngineContract $ruleEngine,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routes = $this->collectRoutes();

        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Security Scan</>');
        $this->line('<fg=gray>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</>');
        $this->line("  Scanning <options=bold>" . count($routes) . "</> route(s)…");
        $this->line('');

        $violations = $this->evaluateRoutes($routes);

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
        $highCount     = count($violations->high());

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
        $this->line('');

        return ($criticalCount > 0 || $highCount > 0) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Scan all routes and return a merged ViolationCollection.
     *
     * @param list<Route> $routes
     */
    private function evaluateRoutes(array $routes): ViolationCollection
    {
        $all = new ViolationCollection();

        foreach ($routes as $route) {
            $context    = $this->buildContext($route);
            $violations = $this->ruleEngine->run($context);
            $all        = $all->merge($violations);
        }

        return $all;
    }

    /**
     * Return routes that are worth scanning (skip framework internals).
     *
     * @return list<Route>
     */
    private function collectRoutes(): array
    {
        $skipPrefixes = ['_ignition', '_telescope', 'horizon/', 'telescope/', 'debugbar/'];

        $routes = [];

        foreach ($this->router->getRoutes() as $route) {
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

    /**
     * Build a synthetic SecurityRuntimeContext from a route definition.
     * Only Route + Request signals are populated — enough for all built-in rules.
     */
    private function buildContext(Route $route): SecurityRuntimeContext
    {
        $methods    = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
        $method     = $this->pickPrimaryMethod(array_values($methods));

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
            capturedAt: new DateTimeImmutable(),
        );

        return (new RuntimeContextBuilder())
            ->withRoute($routeSignal)
            ->withRequest($requestSignal)
            ->build();
    }

    /**
     * Prefer mutable methods so state-changing rules have the best chance to fire.
     *
     * @param list<string> $methods
     */
    private function pickPrimaryMethod(array $methods): string
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'GET'] as $preferred) {
            if (in_array($preferred, $methods, true)) {
                return $preferred;
            }
        }

        return $methods[0] ?? 'GET';
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
            $label    = "<fg={$severity->color()}>{$severity->label()}</>";

            $rows[] = [
                $violation->route !== '' ? $violation->route : '—',
                $violation->title,
                $label,
            ];
        }

        $this->table(['Route / URI', 'Rule', 'Severity'], $rows);
    }
}
