<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Artisan command that lists all registered routes alongside their
 * security protection coverage — auth, CSRF, and rate-limiting.
 *
 * Usage: php artisan runtime-shield:routes
 */
final class RoutesCommand extends Command
{
    protected $signature = 'runtime-shield:routes
                            {--filter= : Filter rows: "exposed" shows only routes with missing protections}';

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
}
