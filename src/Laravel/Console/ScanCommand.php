<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;

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
}
