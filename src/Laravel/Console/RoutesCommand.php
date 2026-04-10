<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;

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
}
