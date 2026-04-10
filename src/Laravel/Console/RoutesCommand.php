<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that lists all registered routes alongside their
 * security protection coverage — auth, CSRF, and rate-limiting.
 *
 * Usage: php artisan runtime-shield:routes
 */
final class RoutesCommand extends Command
{
    protected $signature = 'runtime-shield:routes
                            {--filter= : Filter rows: "exposed" shows only routes with missing protections}
                            {--method= : Show only routes matching this HTTP method (GET, POST, …)}';

    protected $description = 'List all routes with their security protection coverage';

    /** @var list<string> */
    private const SKIP_PREFIXES = ['_ignition', '_telescope', 'horizon/', 'telescope/', 'debugbar/'];

    public function __construct(
        private readonly Router $router,
        private readonly RouteProtectionAnalyzer $analyzer,
    ) {
        parent::__construct();
    }

    /**
     * @return list<RouteProtection>
     */
    private function buildProtections(): array
    {
        $protections = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $uri  = $route->uri();
            $skip = false;

            foreach (self::SKIP_PREFIXES as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $protections[] = $this->buildProtection($route);
        }

        return $protections;
    }

    private function buildProtection(Route $route): RouteProtection
    {
        $methods = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
        $method  = $this->pickMethod(array_values($methods));

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

        return new RouteProtection(
            method: $method,
            uri: $route->uri(),
            name: (string) ($route->getName() ?? ''),
            hasAuth: $this->analyzer->hasAuth($routeSignal),
            hasCsrf: $this->analyzer->hasCsrf($routeSignal, $method),
            hasRateLimit: $this->analyzer->hasRateLimit($routeSignal),
            violations: new ViolationCollection(),
        );
    }

    /** @param list<string> $methods */
    private function pickMethod(array $methods): string
    {
        foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'GET'] as $preferred) {
            if (in_array($preferred, $methods, true)) {
                return $preferred;
            }
        }

        return $methods[0] ?? 'GET';
    }

    public function handle(): int
    {
        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Route Protection Inspector</>');
        $this->line(CliRenderer::divider(56));

        $protections = $this->buildProtections();

        $filter = $this->option('filter');

        if ($filter === 'exposed') {
            $protections = array_values(
                array_filter($protections, static fn (RouteProtection $p): bool => ! $p->isFullyProtected()),
            );
        }

        $method = $this->option('method');

        if (is_string($method) && $method !== '') {
            $upper       = strtoupper($method);
            $protections = array_values(
                array_filter($protections, static fn (RouteProtection $p): bool => $p->method === $upper),
            );
        }

        if ($protections === []) {
            $this->line('<fg=green>  ✔ All routes are fully protected.</>');
            $this->line('');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($protections as $protection) {
            $csrfLabel = $protection->hasCsrf ? CliRenderer::checkmark(true) : CliRenderer::checkmark(false);
            $risk      = CliRenderer::riskLabel($protection->riskLabel());

            $rows[] = [
                $protection->method,
                $protection->uri,
                $protection->name !== '' ? $protection->name : '—',
                CliRenderer::checkmark($protection->hasAuth),
                $csrfLabel,
                CliRenderer::checkmark($protection->hasRateLimit),
                $risk,
            ];
        }

        $this->table(['Method', 'URI', 'Name', 'Auth', 'CSRF', 'Rate Limit', 'Status'], $rows);

        $total    = count($protections);
        $exposed  = count(array_filter($protections, static fn (RouteProtection $p): bool => ! $p->isFullyProtected()));
        $safe     = $total - $exposed;

        $this->line('');
        $this->line("  <options=bold>{$total}</> route(s) shown   <fg=green>{$safe} protected</>   <fg=red>{$exposed} exposed</>");
        $this->line('');

        return self::SUCCESS;
    }
}
