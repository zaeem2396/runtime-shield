<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Signal;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use RuntimeShield\Contracts\Signal\RouteCollectorContract;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Extracts route metadata from a resolved Laravel Request.
 * Returns null when no Route has been matched (404, console, etc.).
 */
final class RouteSignalCollector implements RouteCollectorContract
{
    public function collect(Request $request): RouteSignal|null
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        $all = $route->gatherMiddleware();

        /** @var list<string> $middleware */
        $middleware = array_values(array_filter($all, 'is_string'));

        return new RouteSignal(
            name: (string) ($route->getName() ?? ''),
            uri: $route->uri(),
            action: $route->getActionName(),
            controller: (string) ($route->getControllerClass() ?? ''),
            middleware: $middleware,
            hasNamedRoute: $route->getName() !== null,
        );
    }
}
