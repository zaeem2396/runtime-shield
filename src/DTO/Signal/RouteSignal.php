<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

/**
 * Immutable snapshot of the matched route for the current request.
 */
final class RouteSignal
{
    /**
     * @param list<string> $middleware All middleware registered on the route
     */
    public function __construct(
        public readonly string $name,
        public readonly string $uri,
        public readonly string $action,
        public readonly string $controller,
        public readonly array $middleware,
        public readonly bool $hasNamedRoute,
    ) {
    }
}
