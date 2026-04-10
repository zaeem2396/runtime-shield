<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Report;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Contracts\Report\ReportBuilderContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Builds a SecurityReport by scanning all registered application routes,
 * evaluating the rule engine against each one, and aggregating violations
 * alongside per-route protection metadata.
 */
final class ReportBuilder implements ReportBuilderContract
{
    /** @var list<string> */
    private const SKIP_PREFIXES = ['_ignition', '_telescope', 'horizon/', 'telescope/', 'debugbar/'];
    public function __construct(
        private readonly Router $router,
        private readonly RuleEngineContract $ruleEngine,
        private readonly RouteProtectionAnalyzer $analyzer,
    ) {
    }

    public function build(): SecurityReport
    {
        $routes = $this->collectRoutes();
        $all = new ViolationCollection();
        $protections = [];

        foreach ($routes as $route) {
            $context = $this->buildContext($route);
            $violations = $this->ruleEngine->run($context);
            $all = $all->merge($violations);

            $routeSignal = $context->route;

            if ($routeSignal !== null) {
                $method = $context->request?->method ?? 'GET';
                $protections[] = new RouteProtection(
                    method: $method,
                    uri: $routeSignal->uri,
                    name: $routeSignal->name,
                    hasAuth: $this->analyzer->hasAuth($routeSignal),
                    hasCsrf: $this->analyzer->hasCsrf($routeSignal, $method),
                    hasRateLimit: $this->analyzer->hasRateLimit($routeSignal),
                    violations: $violations,
                );
            }
        }

        return new SecurityReport(
            scannedAt: new \DateTimeImmutable(),
            routeCount: count($routes),
            violations: $all,
            routeProtections: $protections,
        );
    }

    /**
     * @return list<Route>
     */
    private function collectRoutes(): array
    {
        $routes = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $skip = false;

            foreach (self::SKIP_PREFIXES as $prefix) {
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

    private function buildContext(Route $route): \RuntimeShield\DTO\SecurityRuntimeContext
    {
        $methods = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
        $method = $this->pickMethod(array_values($methods));

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
