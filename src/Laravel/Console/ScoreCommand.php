<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Score\CategoryScore;
use RuntimeShield\DTO\Score\SecurityScore;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that calculates a weighted security score for the application
 * by scanning all registered routes and evaluating built-in rules.
 *
 * Usage: php artisan runtime-shield:score
 */
final class ScoreCommand extends Command
{
    protected $signature = 'runtime-shield:score
                            {--format=table : Output format (table|json)}';

    protected $description = 'Calculate a weighted security score with per-category breakdown';

    public function __construct(
        private readonly Router $router,
        private readonly RuleEngineContract $ruleEngine,
        private readonly ScoreEngineContract $scoreEngine,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $routes     = $this->collectRoutes();
        $violations = $this->evaluateRoutes($routes);
        $score      = $this->scoreEngine->calculate($violations);

        if ($this->option('format') === 'json') {
            $this->renderJson($score);

            return self::SUCCESS;
        }

        $this->renderHeader();
        $this->renderScorePanel($score);
        $this->renderCategoryTable($score);
        $this->renderHighestRisk($score);
        $this->renderFailedCategoriesWarning($score);

        return self::SUCCESS;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Route collection & evaluation
    // ────────────────────────────────────────────────────────────────────────

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
     * Return application routes, skipping internal framework URIs.
     *
     * @return list<Route>
     */
    private function collectRoutes(): array
    {
        $skipPrefixes = ['_ignition', '_telescope', 'horizon/', 'telescope/', 'debugbar/'];
        $routes       = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $uri  = $route->uri();
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

    /** Build a synthetic SecurityRuntimeContext from a route definition. */
    private function buildContext(Route $route): SecurityRuntimeContext
    {
        $methods = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
        $method  = $this->pickPrimaryMethod(array_values($methods));

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

    /**
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

    // ────────────────────────────────────────────────────────────────────────
    // Rendering helpers
    // ────────────────────────────────────────────────────────────────────────

    private function renderHeader(): void
    {
        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Security Score</>');
        $this->line(CliRenderer::divider(50));
        $this->line('');
    }

    private function renderScorePanel(SecurityScore $score): void
    {
        $gradeColor = CliRenderer::gradeColor($score->grade);
        $scoreColor = CliRenderer::scoreColor($score->overall);

        $this->line(sprintf(
            '  Security Score:  <fg=%s;options=bold>%d / 100</>',
            $scoreColor,
            $score->overall,
        ));

        $this->line(sprintf(
            '  Grade:           <fg=%s;options=bold>%s</>',
            $gradeColor,
            $score->grade,
        ));

        $this->line(sprintf(
            '  Total Violations: <options=bold>%d</>',
            $score->totalViolations,
        ));

        $this->line('');
    }

    private function renderCategoryTable(SecurityScore $score): void
    {
        $this->line('<options=bold>  Category Breakdown:</>');
        $this->line('');

        $rows = [];

        foreach ($score->sortedByRisk() as $cs) {
            $rows[] = $this->buildCategoryRow($cs);
        }

        $this->table(
            ['Category', 'Score', 'Coverage', 'Weight', 'Violations'],
            $rows,
        );
    }

    /**
     * @return list<string>
     */
    private function buildCategoryRow(CategoryScore $cs): array
    {
        $scoreColor = CliRenderer::scoreColor($cs->score);
        $bar        = CliRenderer::progressBar($cs->score);

        return [
            $cs->category->label(),
            sprintf('<fg=%s;options=bold>%d / 100</>', $scoreColor, $cs->score),
            $bar,
            $cs->weight . '%',
            (string) $cs->violationCount,
        ];
    }

    private function renderHighestRisk(SecurityScore $score): void
    {
        $risk = $score->highestRisk();

        if ($risk === null || $risk->score >= 75) {
            return;
        }

        $color = CliRenderer::scoreColor($risk->score);

        $this->line(sprintf(
            '  Highest risk area: <fg=%s;options=bold>%s</> (<fg=%s>%d/100</>)',
            $color,
            $risk->category->label(),
            $color,
            $risk->score,
        ));

        $this->line(sprintf(
            '  → %s',
            $risk->category->description(),
        ));

        $this->line('');
    }

    private function renderFailedCategoriesWarning(SecurityScore $score): void
    {
        $failed = $score->failedCategories();

        if ($failed === []) {
            $this->line('');
            $this->line('<fg=green>  ✔ All categories are passing (score ≥ 75).</>');
            $this->line('');

            return;
        }

        $this->line('');
        $this->line('<fg=red;options=bold>  ✘ Categories below the passing threshold (75):</>');

        foreach ($failed as $cs) {
            $this->line(sprintf(
                '    · <fg=red>%s</> — score %d/100 (%d violation(s))',
                $cs->category->label(),
                $cs->score,
                $cs->violationCount,
            ));
        }

        $this->line('');
    }

    private function renderJson(SecurityScore $score): void
    {
        $this->line((string) json_encode(
            $score->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));
    }
}
