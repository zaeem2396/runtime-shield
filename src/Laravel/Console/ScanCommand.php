<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

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
    ) {
        parent::__construct();
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
}
