<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Plugin;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Plugin\PluginContract;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Contracts\Signal\CustomSignalCollectorContract;
use RuntimeShield\Core\Plugin\PluginRegistry;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Core\Signal\CustomSignalRegistry;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class PluginRegistryTest extends TestCase
{
    // ------------------------------------------------------------------ register / all

    #[Test]
    public function starts_empty(): void
    {
        $registry = new PluginRegistry();

        $this->assertSame(0, $registry->count());
        $this->assertSame([], $registry->all());
    }

    #[Test]
    public function register_adds_plugin(): void
    {
        $registry = new PluginRegistry();
        $registry->register($this->makePlugin('my-plugin'));

        $this->assertSame(1, $registry->count());
    }

    #[Test]
    public function all_returns_registered_plugins_in_order(): void
    {
        $registry = new PluginRegistry();
        $registry->register($this->makePlugin('a'));
        $registry->register($this->makePlugin('b'));

        $all = $registry->all();

        $this->assertCount(2, $all);
        $this->assertSame('a', $all[0]->id());
        $this->assertSame('b', $all[1]->id());
    }

    // ------------------------------------------------------------------ has / find

    #[Test]
    public function has_returns_true_for_registered_plugin(): void
    {
        $registry = new PluginRegistry();
        $registry->register($this->makePlugin('p1'));

        $this->assertTrue($registry->has('p1'));
        $this->assertFalse($registry->has('p2'));
    }

    #[Test]
    public function find_returns_plugin_by_id(): void
    {
        $registry = new PluginRegistry();
        $plugin = $this->makePlugin('found');
        $registry->register($plugin);

        $this->assertSame($plugin, $registry->find('found'));
        $this->assertNull($registry->find('missing'));
    }

    // ------------------------------------------------------------------ boot

    #[Test]
    public function boot_registers_plugin_rules_into_rule_registry(): void
    {
        $pluginRegistry = new PluginRegistry();
        $ruleRegistry = new RuleRegistry();
        $signalRegistry = new CustomSignalRegistry();

        $plugin = $this->makePlugin('plugin-a', rules: [
            $this->makeRule('rule-1'),
            $this->makeRule('rule-2'),
        ]);

        $pluginRegistry->register($plugin);
        $pluginRegistry->boot($ruleRegistry, $signalRegistry);

        $this->assertSame(2, $ruleRegistry->count());
        $this->assertNotNull($ruleRegistry->find('rule-1'));
        $this->assertNotNull($ruleRegistry->find('rule-2'));
    }

    #[Test]
    public function boot_registers_plugin_collectors_into_signal_registry(): void
    {
        $pluginRegistry = new PluginRegistry();
        $ruleRegistry = new RuleRegistry();
        $signalRegistry = new CustomSignalRegistry();

        $plugin = $this->makePlugin('plugin-b', collectors: [
            $this->makeCollector('collector-1'),
        ]);

        $pluginRegistry->register($plugin);
        $pluginRegistry->boot($ruleRegistry, $signalRegistry);

        $this->assertSame(1, $signalRegistry->count());
        $this->assertTrue($signalRegistry->has('collector-1'));
    }

    #[Test]
    public function boot_calls_plugin_boot_method(): void
    {
        $pluginRegistry = new PluginRegistry();
        $bootCalled = false;
        $plugin = $this->makePlugin('booted', bootCalled: $bootCalled);

        $pluginRegistry->register($plugin);
        $pluginRegistry->boot(new RuleRegistry(), new CustomSignalRegistry());

        $this->assertTrue($bootCalled);
    }

    #[Test]
    public function boot_processes_multiple_plugins(): void
    {
        $pluginRegistry = new PluginRegistry();
        $ruleRegistry = new RuleRegistry();
        $signalRegistry = new CustomSignalRegistry();

        $pluginRegistry->register($this->makePlugin('p1', rules: [$this->makeRule('r1')]));
        $pluginRegistry->register($this->makePlugin('p2', rules: [$this->makeRule('r2'), $this->makeRule('r3')]));
        $pluginRegistry->boot($ruleRegistry, $signalRegistry);

        $this->assertSame(3, $ruleRegistry->count());
    }

    #[Test]
    public function boot_on_empty_registry_is_safe(): void
    {
        $registry = new PluginRegistry();

        $registry->boot(new RuleRegistry(), new CustomSignalRegistry());

        $this->assertSame(0, $registry->count());
    }
    // ------------------------------------------------------------------ helpers

    private function makePlugin(
        string $id,
        string $name = '',
        array $rules = [],
        array $collectors = [],
        bool &$bootCalled = false,
    ): PluginContract {
        return new class ($id, $name ?: "Plugin {$id}", $rules, $collectors, $bootCalled) implements PluginContract {
            public function __construct(
                private readonly string $pluginId,
                private readonly string $pluginName,
                private readonly array $pluginRules,
                private readonly array $pluginCollectors,
                private bool &$bootFlag,
            ) {
            }

            public function id(): string
            {
                return $this->pluginId;
            }

            public function name(): string
            {
                return $this->pluginName;
            }

            public function rules(): array
            {
                return $this->pluginRules;
            }

            public function signalCollectors(): array
            {
                return $this->pluginCollectors;
            }

            public function boot(): void
            {
                $this->bootFlag = true;
            }
        };
    }

    private function makeRule(string $id): RuleContract
    {
        return new class ($id) implements RuleContract {
            public function __construct(private readonly string $ruleId)
            {
            }

            public function id(): string
            {
                return $this->ruleId;
            }

            public function title(): string
            {
                return 'Rule ' . $this->ruleId;
            }

            public function severity(): Severity
            {
                return Severity::LOW;
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                return [];
            }
        };
    }

    private function makeCollector(string $id): CustomSignalCollectorContract
    {
        return new class ($id) implements CustomSignalCollectorContract {
            public function __construct(private readonly string $collectorId)
            {
            }

            public function id(): string
            {
                return $this->collectorId;
            }

            public function collect(Request $request): array
            {
                return [];
            }
        };
    }
}
