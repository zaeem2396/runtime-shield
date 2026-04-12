<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Plugin;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Contracts\Signal\CustomSignalCollectorContract;
use RuntimeShield\Core\Plugin\AbstractPlugin;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class AbstractPluginTest extends TestCase
{
    // ------------------------------------------------------------------ defaults

    #[Test]
    public function rules_returns_empty_array_by_default(): void
    {
        $plugin = $this->minimalPlugin('test', 'Test Plugin');

        $this->assertSame([], $plugin->rules());
    }

    #[Test]
    public function signal_collectors_returns_empty_array_by_default(): void
    {
        $plugin = $this->minimalPlugin('test', 'Test Plugin');

        $this->assertSame([], $plugin->signalCollectors());
    }

    #[Test]
    public function boot_does_not_throw(): void
    {
        $plugin = $this->minimalPlugin('test', 'Test Plugin');

        $this->expectNotToPerformAssertions();
        $plugin->boot();
    }

    // ------------------------------------------------------------------ id / name contract

    #[Test]
    public function id_and_name_are_returned_correctly(): void
    {
        $plugin = $this->minimalPlugin('acme/my-plugin', 'Acme Plugin');

        $this->assertSame('acme/my-plugin', $plugin->id());
        $this->assertSame('Acme Plugin', $plugin->name());
    }

    // ------------------------------------------------------------------ override rules / collectors

    #[Test]
    public function subclass_can_override_rules(): void
    {
        $rule = new class () implements RuleContract {
            public function id(): string
            {
                return 'custom-rule';
            }

            public function title(): string
            {
                return 'Custom Rule';
            }

            public function severity(): Severity
            {
                return Severity::MEDIUM;
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                return [];
            }
        };

        $plugin = new class ($rule) extends AbstractPlugin {
            public function __construct(private readonly RuleContract $innerRule)
            {
            }

            public function id(): string
            {
                return 'plugin-with-rules';
            }

            public function name(): string
            {
                return 'Plugin With Rules';
            }

            public function rules(): array
            {
                return [$this->innerRule];
            }
        };

        $this->assertCount(1, $plugin->rules());
        $this->assertSame('custom-rule', $plugin->rules()[0]->id());
    }

    #[Test]
    public function subclass_can_override_signal_collectors(): void
    {
        $collector = new class () implements CustomSignalCollectorContract {
            public function id(): string
            {
                return 'my-collector';
            }

            public function collect(Request $request): array
            {
                return ['key' => 'value'];
            }
        };

        $plugin = new class ($collector) extends AbstractPlugin {
            public function __construct(private readonly CustomSignalCollectorContract $collector)
            {
            }

            public function id(): string
            {
                return 'plugin-with-collectors';
            }

            public function name(): string
            {
                return 'Plugin With Collectors';
            }

            public function signalCollectors(): array
            {
                return [$this->collector];
            }
        };

        $this->assertCount(1, $plugin->signalCollectors());
        $this->assertSame('my-collector', $plugin->signalCollectors()[0]->id());
    }

    #[Test]
    public function subclass_can_override_boot(): void
    {
        $booted = false;

        $plugin = new class ($booted) extends AbstractPlugin {
            public function __construct(private bool &$bootedRef)
            {
            }

            public function id(): string
            {
                return 'boot-plugin';
            }

            public function name(): string
            {
                return 'Boot Plugin';
            }

            public function boot(): void
            {
                $this->bootedRef = true;
            }
        };

        $plugin->boot();

        $this->assertTrue($booted);
    }
    // ------------------------------------------------------------------ helpers

    private function minimalPlugin(string $id, string $name): AbstractPlugin
    {
        return new class ($id, $name) extends AbstractPlugin {
            public function __construct(
                private readonly string $pluginId,
                private readonly string $pluginName,
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
        };
    }
}
