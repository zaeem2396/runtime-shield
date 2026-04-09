<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\ShieldContract;
use RuntimeShield\Core\ConfigRepository;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\Engine\RuntimeShieldEngine;
use RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider;

final class RuntimeShieldServiceProviderTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [RuntimeShieldServiceProvider::class];
    }

    #[Test]
    public function it_merges_package_config_under_runtime_shield_key(): void
    {
        $config = config('runtime_shield');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('sampling_rate', $config);
        $this->assertArrayHasKey('rules', $config);
        $this->assertArrayHasKey('performance', $config);
    }

    #[Test]
    public function it_resolves_config_repository_contract(): void
    {
        $repo = $this->app->make(ConfigRepositoryContract::class);

        $this->assertInstanceOf(ConfigRepository::class, $repo);
    }

    #[Test]
    public function it_returns_the_same_config_repository_singleton(): void
    {
        $a = $this->app->make(ConfigRepositoryContract::class);
        $b = $this->app->make(ConfigRepositoryContract::class);

        $this->assertSame($a, $b);
    }

    #[Test]
    public function it_resolves_runtime_shield_manager(): void
    {
        $manager = $this->app->make(RuntimeShieldManager::class);

        $this->assertInstanceOf(RuntimeShieldManager::class, $manager);
    }

    #[Test]
    public function shield_contract_alias_resolves_to_same_manager_instance(): void
    {
        $via_class = $this->app->make(RuntimeShieldManager::class);
        $via_alias = $this->app->make(ShieldContract::class);

        $this->assertSame($via_class, $via_alias);
    }

    #[Test]
    public function it_resolves_engine_contract_to_runtime_shield_engine(): void
    {
        $engine = $this->app->make(EngineContract::class);

        $this->assertInstanceOf(RuntimeShieldEngine::class, $engine);
    }

    #[Test]
    public function it_returns_the_same_engine_singleton(): void
    {
        $a = $this->app->make(EngineContract::class);
        $b = $this->app->make(EngineContract::class);

        $this->assertSame($a, $b);
    }

    #[Test]
    public function config_repository_reads_enabled_from_laravel_config(): void
    {
        config()->set('runtime_shield.enabled', false);

        $repo = $this->app->make(ConfigRepositoryContract::class);

        // New resolution reflects the value at bind time (singleton seeded at register).
        // Manager reads directly from repo, so check the manager instead.
        $manager = $this->app->make(RuntimeShieldManager::class);
        $this->assertFalse($manager->isEnabled());
    }
}
