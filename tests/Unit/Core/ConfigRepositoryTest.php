<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\ConfigRepository;
use RuntimeShield\DTO\RuntimeShieldConfig;

final class ConfigRepositoryTest extends TestCase
{
    #[Test]
    public function it_reports_enabled_when_config_is_true(): void
    {
        $repo = new ConfigRepository(['enabled' => true]);

        $this->assertTrue($repo->isEnabled());
    }

    #[Test]
    public function it_reports_disabled_when_config_is_false(): void
    {
        $repo = new ConfigRepository(['enabled' => false]);

        $this->assertFalse($repo->isEnabled());
    }

    #[Test]
    public function it_returns_the_configured_sampling_rate(): void
    {
        $repo = new ConfigRepository(['sampling_rate' => 0.75]);

        $this->assertSame(0.75, $repo->samplingRate());
    }

    #[Test]
    public function it_gets_enabled_key(): void
    {
        $repo = new ConfigRepository(['enabled' => false]);

        $this->assertFalse($repo->get('enabled'));
    }

    #[Test]
    public function it_gets_sampling_rate_key(): void
    {
        $repo = new ConfigRepository(['sampling_rate' => 0.3]);

        $this->assertSame(0.3, $repo->get('sampling_rate'));
    }

    #[Test]
    public function it_gets_rules_key(): void
    {
        $repo = new ConfigRepository(['rules' => ['auth' => true]]);

        $this->assertSame(['auth' => true], $repo->get('rules'));
    }

    #[Test]
    public function it_gets_performance_key(): void
    {
        $repo = new ConfigRepository(['performance' => ['async' => true]]);

        $this->assertSame(['async' => true], $repo->get('performance'));
    }

    #[Test]
    public function it_returns_the_default_for_an_unknown_key(): void
    {
        $repo = new ConfigRepository([]);

        $this->assertSame('fallback', $repo->get('does_not_exist', 'fallback'));
    }

    #[Test]
    public function it_returns_null_default_for_unknown_key_when_no_default_given(): void
    {
        $repo = new ConfigRepository([]);

        $this->assertNull($repo->get('missing'));
    }

    #[Test]
    public function all_contains_all_expected_keys(): void
    {
        $repo = new ConfigRepository(['enabled' => true, 'sampling_rate' => 1.0]);
        $all = $repo->all();

        $this->assertArrayHasKey('enabled', $all);
        $this->assertArrayHasKey('sampling_rate', $all);
        $this->assertArrayHasKey('rules', $all);
        $this->assertArrayHasKey('performance', $all);
    }

    #[Test]
    public function dto_returns_a_runtime_shield_config_instance(): void
    {
        $repo = new ConfigRepository(['enabled' => true]);

        $this->assertInstanceOf(RuntimeShieldConfig::class, $repo->dto());
    }
}
