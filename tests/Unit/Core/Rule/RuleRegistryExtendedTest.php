<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class RuleRegistryExtendedTest extends TestCase
{
    // ------------------------------------------------------------------ helpers

    private function makeRule(string $id): RuleContract
    {
        return new class ($id) implements RuleContract {
            public function __construct(private readonly string $ruleId) {}

            public function id(): string { return $this->ruleId; }

            public function title(): string { return 'Rule ' . $this->ruleId; }

            public function severity(): Severity { return Severity::LOW; }

            public function evaluate(SecurityRuntimeContext $context): array { return []; }
        };
    }

    // ------------------------------------------------------------------ unregister

    #[Test]
    public function unregister_removes_rule_by_id(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('alpha'));
        $registry->register($this->makeRule('beta'));

        $result = $registry->unregister('alpha');

        $this->assertTrue($result);
        $this->assertSame(1, $registry->count());
        $this->assertNull($registry->find('alpha'));
        $this->assertNotNull($registry->find('beta'));
    }

    #[Test]
    public function unregister_returns_false_when_id_not_found(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('alpha'));

        $this->assertFalse($registry->unregister('nonexistent'));
        $this->assertSame(1, $registry->count());
    }

    #[Test]
    public function unregister_removes_correct_rule_when_multiple_registered(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('first'));
        $registry->register($this->makeRule('second'));
        $registry->register($this->makeRule('third'));

        $registry->unregister('second');

        $this->assertSame(['first', 'third'], $registry->ids());
    }

    // ------------------------------------------------------------------ replace

    #[Test]
    public function replace_swaps_existing_rule(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('rule-x'));

        $replacement = new class implements RuleContract {
            public function id(): string { return 'rule-x'; }

            public function title(): string { return 'Updated Rule X'; }

            public function severity(): Severity { return Severity::HIGH; }

            public function evaluate(SecurityRuntimeContext $context): array { return []; }
        };

        $replaced = $registry->replace($replacement);

        $this->assertTrue($replaced);
        $this->assertSame(1, $registry->count());
        $this->assertSame('Updated Rule X', $registry->find('rule-x')?->title());
    }

    #[Test]
    public function replace_appends_when_id_not_registered(): void
    {
        $registry = new RuleRegistry();

        $replaced = $registry->replace($this->makeRule('brand-new'));

        $this->assertFalse($replaced);
        $this->assertSame(1, $registry->count());
        $this->assertNotNull($registry->find('brand-new'));
    }

    #[Test]
    public function replace_preserves_other_rules(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('keep'));
        $registry->register($this->makeRule('swap'));

        $registry->replace($this->makeRule('swap'));

        $this->assertSame(2, $registry->count());
        $this->assertNotNull($registry->find('keep'));
        $this->assertNotNull($registry->find('swap'));
    }

    // ------------------------------------------------------------------ reset

    #[Test]
    public function reset_clears_all_rules(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('a'));
        $registry->register($this->makeRule('b'));
        $registry->register($this->makeRule('c'));

        $registry->reset();

        $this->assertSame(0, $registry->count());
        $this->assertSame([], $registry->all());
    }

    #[Test]
    public function reset_on_empty_registry_is_safe(): void
    {
        $registry = new RuleRegistry();
        $registry->reset();

        $this->assertSame(0, $registry->count());
    }

    #[Test]
    public function rules_can_be_registered_again_after_reset(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('original'));
        $registry->reset();
        $registry->register($this->makeRule('fresh'));

        $this->assertSame(1, $registry->count());
        $this->assertNotNull($registry->find('fresh'));
    }

    // ------------------------------------------------------------------ ids

    #[Test]
    public function ids_returns_all_registered_ids_in_order(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('first'));
        $registry->register($this->makeRule('second'));
        $registry->register($this->makeRule('third'));

        $this->assertSame(['first', 'second', 'third'], $registry->ids());
    }

    #[Test]
    public function ids_returns_empty_list_on_empty_registry(): void
    {
        $registry = new RuleRegistry();

        $this->assertSame([], $registry->ids());
    }

    #[Test]
    public function ids_reflects_changes_after_unregister(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('a'));
        $registry->register($this->makeRule('b'));
        $registry->unregister('a');

        $this->assertSame(['b'], $registry->ids());
    }
}
