<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Support;

use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;
use RuntimeShield\Support\JsonExportEnvelope;
use RuntimeShield\Support\PackageVersion;

final class JsonExportEnvelopeTest extends TestCase
{
    #[Test]
    public function it_wraps_payload_with_stable_metadata_keys(): void
    {
        $wrapped = JsonExportEnvelope::wrap('score', ['security_score' => ['overall' => 80]]);

        $this->assertSame(1, $wrapped['export_schema_version']);
        $this->assertSame(PackageVersion::VERSION, $wrapped['package_version']);
        $this->assertSame('score', $wrapped['artifact']);
        $this->assertArrayHasKey('generated_at', $wrapped);
        $this->assertSame(['security_score' => ['overall' => 80]], $wrapped['data']);
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }
}
