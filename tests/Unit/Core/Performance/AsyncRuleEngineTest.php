<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Performance;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\Performance\AsyncRuleEngine;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;

final class AsyncRuleEngineTest extends TestCase
{
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

    private function makeInner(int $violations = 3): RuleEngineContract
    {
        return new class($violations) implements RuleEngineContract {
            public function __construct(private readonly int $count)
            {
            }

            public function run(SecurityRuntimeContext $context): ViolationCollection
            {
                return new ViolationCollection(
                    array_fill(0, $this->count, new \RuntimeShield\DTO\Rule\Violation(
                        'test-rule', 'Test', 'Desc', \RuntimeShield\DTO\Rule\Severity::LOW,
                    )),
                );
            }
        };
    }

    public function test_sync_mode_delegates_to_inner_engine(): void
    {
        $engine = new AsyncRuleEngine($this->makeInner(3), async: false);
        $result = $engine->run($this->makeContext());

        $this->assertSame(3, $result->count());
    }

    public function test_is_async_false_in_sync_mode(): void
    {
        $engine = new AsyncRuleEngine($this->makeInner(), async: false);
        $this->assertFalse($engine->isAsync());
    }

    public function test_is_async_true_in_async_mode(): void
    {
        $engine = new AsyncRuleEngine($this->makeInner(), async: true);
        $this->assertTrue($engine->isAsync());
    }

    public function test_sync_mode_returns_correct_violation_count(): void
    {
        $engine = new AsyncRuleEngine($this->makeInner(5), async: false);
        $result = $engine->run($this->makeContext());
        $this->assertSame(5, $result->count());
    }
}
