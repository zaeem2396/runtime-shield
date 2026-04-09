<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\RuntimeShieldConfig;

final class RuntimeShieldConfigTest extends TestCase
{
    #[Test]
    public function it_creates_from_a_full_config_array(): void
    {
        $config = RuntimeShieldConfig::fromArray([
            'enabled'      => true,
            'sampling_rate' => 0.5,
            'rules'        => ['auth' => true],
            'performance'  => ['async' => false],
        ]);

        $this->assertTrue($config->enabled);
        $this->assertSame(0.5, $config->samplingRate);
        $this->assertSame(['auth' => true], $config->rules);
        $this->assertSame(['async' => false], $config->performance);
    }

    #[Test]
    public function it_applies_defaults_for_missing_keys(): void
    {
        $config = RuntimeShieldConfig::fromArray([]);

        $this->assertTrue($config->enabled);
        $this->assertSame(1.0, $config->samplingRate);
        $this->assertSame([], $config->rules);
        $this->assertSame([], $config->performance);
    }

    #[Test]
    public function it_casts_enabled_to_bool(): void
    {
        $config = RuntimeShieldConfig::fromArray(['enabled' => 0]);

        $this->assertFalse($config->enabled);
    }

    #[Test]
    public function it_casts_sampling_rate_to_float(): void
    {
        $config = RuntimeShieldConfig::fromArray(['sampling_rate' => '0.75']);

        $this->assertSame(0.75, $config->samplingRate);
    }

    #[Test]
    public function with_enabled_returns_new_instance_with_flag_changed(): void
    {
        $original = RuntimeShieldConfig::fromArray(['enabled' => true]);
        $disabled = $original->withEnabled(false);

        $this->assertTrue($original->enabled, 'original must not be mutated');
        $this->assertFalse($disabled->enabled);
        $this->assertNotSame($original, $disabled);
    }

    #[Test]
    public function with_enabled_preserves_remaining_fields(): void
    {
        $original = RuntimeShieldConfig::fromArray([
            'enabled'      => true,
            'sampling_rate' => 0.9,
            'rules'        => ['csrf' => true],
            'performance'  => ['timeout_ms' => 50],
        ]);

        $copy = $original->withEnabled(false);

        $this->assertSame(0.9, $copy->samplingRate);
        $this->assertSame(['csrf' => true], $copy->rules);
        $this->assertSame(['timeout_ms' => 50], $copy->performance);
    }

    #[Test]
    public function with_sampling_rate_returns_new_instance(): void
    {
        $original = RuntimeShieldConfig::fromArray(['sampling_rate' => 1.0]);
        $partial  = $original->withSamplingRate(0.25);

        $this->assertSame(1.0, $original->samplingRate, 'original must not be mutated');
        $this->assertSame(0.25, $partial->samplingRate);
    }
}
