<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;

/**
 * Artisan command that generates a comprehensive security report for all
 * registered application routes.
 *
 * Usage: php artisan runtime-shield:report
 */
final class ReportCommand extends Command
{
    protected $signature = 'runtime-shield:report
                            {--format=table : Output format (table|json)}';

    protected $description = 'Generate a full security report for all registered routes';
}
