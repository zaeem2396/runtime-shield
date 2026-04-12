<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Contracts\Plugin\PluginContract;
use RuntimeShield\Core\Plugin\PluginRegistry;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that lists all registered RuntimeShield plugins with
 * their IDs, names, rule counts, and signal collector counts.
 *
 * Usage: php artisan runtime-shield:plugins
 */
final class PluginsCommand extends Command
{
    protected $signature = 'runtime-shield:plugins';

    protected $description = 'List all registered RuntimeShield plugins';

    public function __construct(private readonly PluginRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $plugins = $this->registry->all();

        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Plugins</>');
        $this->line(CliRenderer::divider(50));
        $this->line('');

        $total = count($plugins);
        $this->line("  Registered plugins: <options=bold>{$total}</>");
        $this->line('');

        if ($total === 0) {
            $this->line('  <fg=gray>No plugins registered.</>');
            $this->line('  <fg=gray>Add plugin class names to config(\'runtime_shield.extensibility.plugins\').</>');
            $this->line('');

            return self::SUCCESS;
        }

        $rows = array_map(static fn (PluginContract $p): array => [
            $p->id(),
            $p->name(),
            (string) count($p->rules()),
            (string) count($p->signalCollectors()),
        ], $plugins);

        $this->table(['ID', 'Name', 'Rules', 'Collectors'], $rows);
        $this->line('');

        return self::SUCCESS;
    }
}
