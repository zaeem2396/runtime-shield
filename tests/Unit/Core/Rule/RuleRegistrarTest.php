<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Core\Rule\RuleRegistrar;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class RuleRegistrarTest extends TestCase
{
    // ------------------------------------------------------------------ rule() / rules()

    #[Test]
    public function rule_registers_single_rule(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rule($this->makeRule('my-rule'));

        $this->assertSame(1, $registrar->registry()->count());
        $this->assertNotNull($registrar->registry()->find('my-rule'));
    }

    #[Test]
    public function rules_registers_multiple_rules(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rules([
            $this->makeRule('a'),
            $this->makeRule('b'),
            $this->makeRule('c'),
        ]);

        $this->assertSame(3, $registrar->registry()->count());
    }

    #[Test]
    public function rule_and_rules_are_chainable(): void
    {
        $registrar = $this->freshRegistrar();

        $result = $registrar
            ->rule($this->makeRule('x'))
            ->rules([$this->makeRule('y'), $this->makeRule('z')]);

        $this->assertSame($registrar, $result);
        $this->assertSame(3, $registrar->registry()->count());
    }

    // ------------------------------------------------------------------ disable()

    #[Test]
    public function disable_removes_rule_by_id(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rule($this->makeRule('to-remove'))
            ->rule($this->makeRule('keep'));

        $registrar->disable('to-remove');

        $this->assertNull($registrar->registry()->find('to-remove'));
        $this->assertNotNull($registrar->registry()->find('keep'));
    }

    #[Test]
    public function disable_silently_noop_when_not_found(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rule($this->makeRule('keep'));

        $result = $registrar->disable('nonexistent');

        $this->assertSame($registrar, $result);
        $this->assertSame(1, $registrar->registry()->count());
    }

    #[Test]
    public function disable_is_chainable(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rule($this->makeRule('a'))
            ->rule($this->makeRule('b'));

        $result = $registrar->disable('a')->disable('b');

        $this->assertSame($registrar, $result);
        $this->assertSame(0, $registrar->registry()->count());
    }

    // ------------------------------------------------------------------ replace()

    #[Test]
    public function replace_swaps_rule_with_same_id(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rule($this->makeRule('rule-1', Severity::LOW));

        $registrar->replace($this->makeRule('rule-1', Severity::CRITICAL));

        $found = $registrar->registry()->find('rule-1');
        $this->assertNotNull($found);
        $this->assertSame(Severity::CRITICAL, $found->severity());
    }

    #[Test]
    public function replace_appends_when_id_not_registered(): void
    {
        $registrar = $this->freshRegistrar();

        $registrar->replace($this->makeRule('brand-new'));

        $this->assertSame(1, $registrar->registry()->count());
        $this->assertNotNull($registrar->registry()->find('brand-new'));
    }

    #[Test]
    public function replace_is_chainable(): void
    {
        $registrar = $this->freshRegistrar();
        $registrar->rule($this->makeRule('x'));

        $result = $registrar->replace($this->makeRule('x', Severity::HIGH));

        $this->assertSame($registrar, $result);
    }

    // ------------------------------------------------------------------ registry()

    #[Test]
    public function registry_returns_underlying_rule_registry(): void
    {
        $inner = new RuleRegistry();
        $registrar = new RuleRegistrar($inner);

        $this->assertSame($inner, $registrar->registry());
    }

    // ------------------------------------------------------------------ fluent chain integration

    #[Test]
    public function full_fluent_chain_works_correctly(): void
    {
        $registrar = $this->freshRegistrar();

        $registrar
            ->rules([
                $this->makeRule('base-a'),
                $this->makeRule('base-b'),
                $this->makeRule('base-c'),
            ])
            ->disable('base-b')
            ->replace($this->makeRule('base-a', Severity::HIGH))
            ->rule($this->makeRule('custom'));

        $registry = $registrar->registry();

        $this->assertSame(3, $registry->count());
        $this->assertSame(Severity::HIGH, $registry->find('base-a')?->severity());
        $this->assertNull($registry->find('base-b'));
        $this->assertNotNull($registry->find('base-c'));
        $this->assertNotNull($registry->find('custom'));
    }
    // ------------------------------------------------------------------ helpers

    private function makeRule(string $id, Severity $severity = Severity::LOW): RuleContract
    {
        return new class ($id, $severity) implements RuleContract {
            public function __construct(
                private readonly string $ruleId,
                private readonly Severity $ruleSeverity,
            ) {
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
                return $this->ruleSeverity;
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                return [];
            }
        };
    }

    private function freshRegistrar(): RuleRegistrar
    {
        return new RuleRegistrar(new RuleRegistry());
    }
}
