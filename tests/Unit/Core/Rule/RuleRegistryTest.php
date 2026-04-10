<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class RuleRegistryTest extends TestCase
{
    private function makeRule(string $id = 'test-rule'): RuleContract
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
                return 'Test Rule';
            }

            public function severity(): Severity
            {
                return Severity::INFO;
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                return [];
            }
        };
    }

    #[Test]
    public function it_starts_empty(): void
    {
        $registry = new RuleRegistry();

        $this->assertSame(0, $registry->count());
        $this->assertSame([], $registry->all());
    }

    #[Test]
    public function it_registers_and_counts_rules(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('rule-a'));
        $registry->register($this->makeRule('rule-b'));

        $this->assertSame(2, $registry->count());
        $this->assertCount(2, $registry->all());
    }

    #[Test]
    public function has_returns_true_for_registered_rule(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('my-rule'));

        $this->assertTrue($registry->has('my-rule'));
        $this->assertFalse($registry->has('other-rule'));
    }

    #[Test]
    public function find_returns_rule_when_present(): void
    {
        $registry = new RuleRegistry();
        $rule     = $this->makeRule('find-me');
        $registry->register($rule);

        $this->assertSame($rule, $registry->find('find-me'));
    }

    #[Test]
    public function find_returns_null_when_absent(): void
    {
        $registry = new RuleRegistry();

        $this->assertNull($registry->find('ghost'));
    }
}
