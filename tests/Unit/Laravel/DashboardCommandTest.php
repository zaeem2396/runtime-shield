<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;

final class DashboardCommandTest extends TestCase
{
    #[Test]
    public function it_runs_the_dashboard_command_in_table_mode(): void
    {
        $exit = Artisan::call('runtime-shield:dashboard', ['--format' => 'table']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('RuntimeShield Debug Dashboard', Artisan::output());
    }

    #[Test]
    public function it_outputs_json_payload_when_requested(): void
    {
        $exit = Artisan::call('runtime-shield:dashboard', ['--format' => 'json']);

        $this->assertSame(0, $exit);
        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('metrics_summary', $decoded);
        $this->assertArrayHasKey('recent_middleware_metrics', $decoded);
    }

    #[Test]
    public function it_honors_the_samples_option_in_json_mode(): void
    {
        $exit = Artisan::call('runtime-shield:dashboard', [
            '--format' => 'json',
            '--samples' => 0,
        ]);

        $this->assertSame(0, $exit);
        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded);
        $this->assertSame([], $decoded['recent_middleware_metrics']);
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }
}
