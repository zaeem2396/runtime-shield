<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;

final class ScoreCommandTest extends TestCase
{
    #[Test]
    public function it_outputs_valid_json_when_format_is_json(): void
    {
        $exit = Artisan::call('runtime-shield:score', ['--format' => 'json']);

        $this->assertSame(0, $exit);
        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('overall', $decoded);
        $this->assertArrayHasKey('grade', $decoded);
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }
}
