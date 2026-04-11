<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Core\Performance\BatchedRuleEngine;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;

final class BatchedRuleEngineTest extends TestCase
{
    public function test_empty_registry_returns_empty_collection(): void
    {
        $engine = new BatchedRuleEngine(new RuleRegistry(), 10, 100);
        $result = $engine->run($this->makeContext());

        $this->assertTrue($result->isEmpty());
    }

    public function test_all_rules_evaluated_within_batch_size(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('rule-1', 1));
        $registry->register($this->makeRule('rule-2', 1));
        $registry->register($this->makeRule('rule-3', 1));

        $engine = new BatchedRuleEngine($registry, 5, 100);
        $result = $engine->run($this->makeContext());

        $this->assertSame(3, $result->count());
    }

    public function test_rules_split_across_multiple_batches(): void
    {
        $registry = new RuleRegistry();

        for ($i = 1; $i <= 5; $i++) {
            $registry->register($this->makeRule("rule-{$i}", 1));
        }

        $engine = new BatchedRuleEngine($registry, 2, 0); // batchSize=2, no timeout
        $result = $engine->run($this->makeContext());

        $this->assertSame(5, $result->count());
    }

    public function test_timeout_zero_disables_timeout_guard(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('rule-1', 3));

        $engine = new BatchedRuleEngine($registry, 10, 0);
        $result = $engine->run($this->makeContext());

        $this->assertSame(3, $result->count());
    }

    public function test_batch_size_getter(): void
    {
        $engine = new BatchedRuleEngine(new RuleRegistry(), 25, 50);
        $this->assertSame(25, $engine->batchSize());
    }

    public function test_timeout_ms_getter(): void
    {
        $engine = new BatchedRuleEngine(new RuleRegistry(), 25, 50);
        $this->assertSame(50, $engine->timeoutMs());
    }

    public function test_batch_size_1_processes_rules_one_at_a_time(): void
    {
        $registry = new RuleRegistry();
        $registry->register($this->makeRule('rule-1', 2));
        $registry->register($this->makeRule('rule-2', 2));

        $engine = new BatchedRuleEngine($registry, 1, 0);
        $result = $engine->run($this->makeContext());

        $this->assertSame(4, $result->count());
    }
    private function makeContext(): SecurityRuntimeContext
    {
        $signal = new RequestSignal(
            method: 'GET',
            url: 'http://localhost/',
            path: '/',
            ip: '127.0.0.1',
            headers: [],
            query: [],
            bodySize: 0,
            capturedAt: new \DateTimeImmutable(),
        );

        return (new RuntimeContextBuilder())->withRequest($signal)->build();
    }

    private function makeRule(string $id, int $violationCount = 1): RuleContract
    {
        return new class ($id, $violationCount) implements RuleContract {
            public function __construct(
                private readonly string $ruleId,
                private readonly int $count,
            ) {
            }

            public function id(): string
            {
                return $this->ruleId;
            }

            public function title(): string
            {
                return 'Test';
            }

            public function severity(): Severity
            {
                return Severity::LOW;
            }

            /**
             * @return list<Violation>
             */
            public function evaluate(SecurityRuntimeContext $context): array
            {
                return array_fill(
                    0,
                    $this->count,
                    new Violation($this->ruleId, 'Test', 'Desc', Severity::LOW),
                );
            }
        };
    }
}
