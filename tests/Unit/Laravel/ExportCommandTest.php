<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;

final class ExportCommandTest extends TestCase
{
    #[Test]
    public function it_exports_score_json_to_stdout(): void
    {
        $exit = Artisan::call('runtime-shield:export', ['artifact' => 'score']);

        $this->assertSame(0, $exit);
        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('score', $decoded['artifact']);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertArrayHasKey('security_score', $decoded['data']);
        $this->assertArrayHasKey('violations_summary', $decoded['data']);
    }

    #[Test]
    public function it_writes_report_export_to_a_file(): void
    {
        $path = sys_get_temp_dir() . '/runtime-shield-export-report-' . uniqid('', true) . '.json';

        try {
            $exit = Artisan::call('runtime-shield:export', [
                'artifact' => 'report',
                '--output' => $path,
            ]);

            $this->assertSame(0, $exit);
            $this->assertFileExists($path);
            $decoded = json_decode((string) file_get_contents($path), true);
            $this->assertIsArray($decoded);
            $this->assertSame('report', $decoded['artifact']);
            $this->assertArrayHasKey('report', $decoded['data']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }
}
