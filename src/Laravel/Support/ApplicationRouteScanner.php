<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Collects application routes (skipping framework internals) and evaluates
 * them through the rule engine — shared by score, scan, export, and CI commands.
 */
final class ApplicationRouteScanner
{
    public function __construct(
        private readonly Router $router,
        private readonly RuleEngineContract $ruleEngine,
    ) {
    }

    /**
     * Evaluate every scannable route and merge violations into one collection.
     */
    public function scanRoutes(): ViolationCollection
    {
        return $this->evaluateRoutes($this->collectRoutes());
    }

    /** Number of routes that would be scanned (framework internals excluded). */
    public function scannableRouteCount(): int
    {
        return count($this->collectRoutes());
    }

    /**
     * @param list<Route> $routes
     */
    private function evaluateRoutes(array $routes): ViolationCollection
    {
        $all = new ViolationCollection();

        foreach ($routes as $route) {
            $context = $this->buildContext($route);
            $violations = $this->ruleEngine->run($context);
            $all = $all->merge($violations);
        }

        return $all;
    }

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
        $method = $this->pickPrimaryMethod(array_values($methods));

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
}
