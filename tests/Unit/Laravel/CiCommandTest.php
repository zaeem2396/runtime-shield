<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;

final class CiCommandTest extends TestCase
{
    #[Test]
    public function it_passes_when_gates_are_lenient(): void
    {
        $exit = Artisan::call('runtime-shield:ci', [
            '--min-score' => 0,
            '--max-critical' => 10_000,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('passed', Artisan::output());
    }

    #[Test]
    public function it_fails_when_min_score_is_impossibly_high(): void
    {
        $exit = Artisan::call('runtime-shield:ci', [
            '--min-score' => 101,
            '--max-critical' => 10_000,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('below the required minimum', Artisan::output());
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }
}
