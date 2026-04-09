<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\ConfigRepository;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\Support\PackageVersion;

final class RuntimeShieldManagerTest extends TestCase
{
    #[Test]
    public function it_is_enabled_when_config_is_on_and_rate_is_one(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);

        $this->assertTrue($manager->isEnabled());
    }

    #[Test]
    public function it_is_disabled_when_config_flag_is_off(): void
    {
        $manager = $this->makeManager(enabled: false, rate: 1.0);

        $this->assertFalse($manager->isEnabled());
    }

    #[Test]
    public function it_is_disabled_when_sampling_rate_is_zero(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 0.0);

        $this->assertFalse($manager->isEnabled());
    }

    #[Test]
    public function it_is_disabled_when_sampling_rate_is_negative(): void
    {
        $manager = $this->makeManager(enabled: true, rate: -0.1);

        $this->assertFalse($manager->isEnabled());
    }

    #[Test]
    public function it_is_always_enabled_when_sampling_rate_is_one(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);

        foreach (range(1, 10) as $_) {
            $this->assertTrue($manager->isEnabled());
        }
    }

    #[Test]
    public function it_can_be_force_disabled_at_runtime(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);
        $manager->disable();

        $this->assertFalse($manager->isEnabled());
    }

    #[Test]
    public function force_disable_overrides_config_and_sampling(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);
        $manager->disable();

        // Even with 100 % config-enabled rate it must stay false.
        foreach (range(1, 5) as $_) {
            $this->assertFalse($manager->isEnabled());
        }
    }

    #[Test]
    public function it_can_be_re_enabled_after_force_disable(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);
        $manager->disable();
        $manager->enable();

        $this->assertTrue($manager->isEnabled());
    }

    #[Test]
    public function enable_on_never_force_disabled_manager_is_harmless(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);
        $manager->enable(); // no prior disable()

        $this->assertTrue($manager->isEnabled());
    }

    #[Test]
    public function it_returns_the_package_version_string(): void
    {
        $manager = $this->makeManager(enabled: true, rate: 1.0);

        $this->assertSame(PackageVersion::VERSION, $manager->version());
    }

    // -------------------------------------------------------------------------

    private function makeManager(bool $enabled, float $rate): RuntimeShieldManager
    {
        return new RuntimeShieldManager(
            new ConfigRepository(['enabled' => $enabled, 'sampling_rate' => $rate]),
        );
    }
}
