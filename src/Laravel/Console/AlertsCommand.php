<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that displays the current alert configuration at a glance:
 * global enabled state, minimum severity, throttle window, async mode, and
 * the status of every registered alert channel.
 *
 * Usage: php artisan runtime-shield:alerts
 */
final class AlertsCommand extends Command
{
    protected $signature = 'runtime-shield:alerts';

    protected $description = 'Display active alert channel configuration and status';

    public function __construct(private readonly AlertDispatcherContract $dispatcher)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $enabled = (bool) config('runtime_shield.alerts.enabled', false);
        $minSeverity = strtoupper((string) config('runtime_shield.alerts.min_severity', 'high'));
        $throttle = (int) config('runtime_shield.alerts.throttle_seconds', 300);
        $async = (bool) config('runtime_shield.alerts.async', false);

        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Alert Channels</>');
        $this->line(CliRenderer::divider(50));
        $this->line('');

        $statusLabel = $enabled
            ? '<fg=green;options=bold>ENABLED</>'
            : '<fg=red;options=bold>DISABLED</>';

        $this->line("  Status:           {$statusLabel}");
        $this->line("  Min Severity:     <options=bold>{$minSeverity}</>");
        $this->line("  Throttle:         <options=bold>{$throttle} s</>");
        $this->line("  Async Dispatch:   " . ($async ? '<fg=green>yes</>' : '<fg=gray>no</>'));
        $this->line('');

        if (! $enabled) {
            $this->line('  <fg=gray>Alerts are disabled. Set RUNTIME_SHIELD_ALERTS_ENABLED=true to activate.</>');
            $this->line('');

            return self::SUCCESS;
        }

        $channels = $this->dispatcher->channels();

        if ($channels === []) {
            $this->line('  <fg=gray>No channels registered.</>');
            $this->line('');

            return self::SUCCESS;
        }

        $rows = array_map(static fn (AlertChannelContract $ch): array => [
            $ch->channelName(),
            $ch->isEnabled() ? '<fg=green>✔ On</>' : '<fg=red>✘ Off</>',
            self::channelConfig($ch->channelName()),
        ], $channels);

        $this->table(['Channel', 'Status', 'Config key'], $rows);
        $this->line('');

        return self::SUCCESS;
    }

    /** Return a brief config hint for each known channel name. */
    private static function channelConfig(string $name): string
    {
        return match ($name) {
            'log' => 'alerts.channels.log.channel',
            'webhook' => 'alerts.channels.webhook.url',
            'slack' => 'alerts.channels.slack.url',
            'mail' => 'alerts.channels.mail.recipients',
            default => '—',
        };
    }
}
