<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Rule;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Core\Rule\RuleEngine;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class RuleEngineTest extends TestCase
{
    private function emptyContext(): SecurityRuntimeContext
    {
        return (new RuntimeContextBuilder())->build();
    }

    private function makeRule(int $violationCount, string $id = 'stub-rule'): RuleContract
    {
        return new class ($violationCount, $id) implements RuleContract {
            public function __construct(
                private readonly int $count,
                private readonly string $ruleId,
            ) {
            }

            public function id(): string
            {
                return $this->ruleId;
            }

            public function title(): string
            {
                return 'Stub';
            }

            public function severity(): Severity
            {
                return Severity::INFO;
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                $violations = [];

                for ($i = 0; $i < $this->count; $i++) {
                    $violations[] = new Violation($this->ruleId, 'Stub', 'desc', Severity::INFO);
                }

                return $violations;
            }
        };
    }

    #[Test]
    public function it_returns_empty_collection_when_registry_is_empty(): void
    {
        $engine = new RuleEngine(new RuleRegistry());

        $result = $engine->run($this->emptyContext());

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function it_aggregates_violations_from_all_rules(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule(2, 'rule-a'));
        $registry->register($this->makeRule(1, 'rule-b'));

        $engine = new RuleEngine($registry);
        $result = $engine->run($this->emptyContext());

        $this->assertSame(3, $result->count());
    }

    #[Test]
    public function it_returns_empty_when_rules_fire_no_violations(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule(0, 'quiet-rule'));

        $engine = new RuleEngine($registry);
        $result = $engine->run($this->emptyContext());

        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function it_runs_multiple_rules_independently(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule(1, 'rule-a'));
        $registry->register($this->makeRule(0, 'rule-b'));
        $registry->register($this->makeRule(2, 'rule-c'));

        $result = (new RuleEngine($registry))->run($this->emptyContext());

        $this->assertSame(3, $result->count());
    }
}
