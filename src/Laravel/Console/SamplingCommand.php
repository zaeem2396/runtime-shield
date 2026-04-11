<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeShield\Contracts\SamplerContract;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\EnvironmentSampler;
use RuntimeShield\Core\Sampling\NeverSampler;
use RuntimeShield\Core\Sampling\PercentageSampler;
use RuntimeShield\Core\Sampling\SamplerChain;
use RuntimeShield\Support\CliRenderer;

/**
 * Artisan command that displays the current sampling configuration,
 * active sampler type, and effective sampling rate.
 *
 * Usage: php artisan runtime-shield:sampling
 */
final class SamplingCommand extends Command
{
    protected $signature = 'runtime-shield:sampling';

    protected $description = 'Show the active sampling configuration and effective rate';

    public function __construct(private readonly SamplerContract $sampler)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->line('');
        $this->line('<fg=cyan;options=bold> RuntimeShield Sampling Configuration</>');
        $this->line(CliRenderer::divider(52));
        $this->line('');

        $this->renderSamplerInfo($this->sampler, 0);

        $rate      = $this->sampler->rate();
        $rateColor = CliRenderer::scoreColor((int) ($rate * 100));

        $this->line('');
        $this->line(CliRenderer::divider(52));
        $this->line(sprintf(
            '  Effective Rate: <fg=%s;options=bold>%.0f%%</>',
            $rateColor,
            $rate * 100,
        ));

        if ($rate <= 0.0) {
            $this->line('  <fg=red>⚠ Sampling is disabled — no requests will be processed.</>');
        } elseif ($rate >= 1.0) {
            $this->line('  <fg=green>✔ All requests will be processed.</>');
        } else {
            $this->line(sprintf(
                '  <fg=yellow>~ Approximately %.0f%% of requests will be processed.</>',
                $rate * 100,
            ));
        }

        $this->line(CliRenderer::divider(52));
        $this->line('');

        return self::SUCCESS;
    }

    private function renderSamplerInfo(SamplerContract $sampler, int $depth): void
    {
        $indent = str_repeat('  ', $depth + 1);

        match (true) {
            $sampler instanceof SamplerChain => $this->renderChain($sampler, $depth),
            $sampler instanceof EnvironmentSampler => $this->renderEnvironmentSampler($sampler, $depth),
            $sampler instanceof AlwaysSampler => $this->line("{$indent}Type: <fg=green>AlwaysSampler</> — processes every request"),
            $sampler instanceof NeverSampler  => $this->line("{$indent}Type: <fg=red>NeverSampler</> — skips every request"),
            $sampler instanceof PercentageSampler => $this->line(
                "{$indent}Type: <fg=yellow>PercentageSampler</> — rate <options=bold>" . round($sampler->rate() * 100, 1) . "%</>",
            ),
            default => $this->line("{$indent}Type: " . $sampler::class),
        };
    }

    private function renderChain(SamplerChain $chain, int $depth): void
    {
        $indent = str_repeat('  ', $depth + 1);
        $this->line("{$indent}Type: <fg=cyan>SamplerChain</> — AND logic ({$chain->count()} sampler(s))");

        foreach ($chain->samplers() as $i => $s) {
            $this->line("{$indent}  [" . ($i + 1) . "]");
            $this->renderSamplerInfo($s, $depth + 2);
        }
    }

    private function renderEnvironmentSampler(EnvironmentSampler $sampler, int $depth): void
    {
        $indent  = str_repeat('  ', $depth + 1);
        $env     = $sampler->resolvedEnvironment();
        $configured = $sampler->isEnvironmentConfigured() ? '<fg=green>yes</>' : '<fg=yellow>no (using fallback)</>';

        $this->line("{$indent}Type: <fg=magenta>EnvironmentSampler</>");
        $this->line("{$indent}  Environment: <options=bold>{$env}</>");
        $this->line("{$indent}  Explicitly configured: {$configured}");
        $this->line("{$indent}  Resolved rate: <options=bold>" . round($sampler->rate() * 100, 1) . "%</>");
    }
}
