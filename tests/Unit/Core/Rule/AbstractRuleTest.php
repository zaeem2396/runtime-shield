<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Rule\AbstractRule;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class AbstractRuleTest extends TestCase
{
    // ------------------------------------------------------------------ severity default

    #[Test]
    public function default_severity_is_low(): void
    {
        $rule = $this->concreteRule('my-rule', 'My Rule');

        $this->assertSame(Severity::LOW, $rule->severity());
    }

    #[Test]
    public function severity_can_be_overridden(): void
    {
        $rule = $this->concreteRule('my-rule', 'My Rule', Severity::CRITICAL);

        $this->assertSame(Severity::CRITICAL, $rule->severity());
    }

    // ------------------------------------------------------------------ make() helper

    #[Test]
    public function make_builds_violation_with_rule_id_and_title(): void
    {
        $rule = $this->concreteRule('check-x', 'Check X');
        $context = $this->makeContext();

        $violations = $rule->evaluate($context);

        $this->assertCount(1, $violations);
        $violation = $violations[0];
        $this->assertInstanceOf(Violation::class, $violation);
        $this->assertSame('check-x', $violation->ruleId);
        $this->assertSame('Check X', $violation->title);
    }

    #[Test]
    public function make_uses_rule_severity_by_default(): void
    {
        $rule = $this->concreteRule('check-y', 'Check Y', Severity::HIGH);
        $context = $this->makeContext();

        $violations = $rule->evaluate($context);

        $this->assertSame(Severity::HIGH, $violations[0]->severity);
    }

    #[Test]
    public function make_populates_description_route_and_context(): void
    {
        $rule = $this->concreteRule('check-z', 'Check Z');
        $context = $this->makeContext();

        $violations = $rule->evaluate($context);

        $this->assertSame('Something is wrong', $violations[0]->description);
        $this->assertSame('/test', $violations[0]->route);
        $this->assertSame(['key' => 'val'], $violations[0]->context);
    }

    #[Test]
    public function make_accepts_custom_severity_override(): void
    {
        $rule = new class () extends AbstractRule {
            public function id(): string
            {
                return 'override-sev';
            }

            public function title(): string
            {
                return 'Override Sev';
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                return [$this->make('Critical issue', '/admin', [], Severity::CRITICAL)];
            }
        };

        $violations = $rule->evaluate($this->makeContext());

        $this->assertSame(Severity::CRITICAL, $violations[0]->severity);
        $this->assertSame(Severity::LOW, $rule->severity());
    }

    // ------------------------------------------------------------------ id / title contract

    #[Test]
    public function id_and_title_are_returned_correctly(): void
    {
        $rule = $this->concreteRule('my-id', 'My Title');

        $this->assertSame('my-id', $rule->id());
        $this->assertSame('My Title', $rule->title());
    }
    // ------------------------------------------------------------------ helpers

    private function makeContext(): SecurityRuntimeContext
    {
        return new SecurityRuntimeContext(
            requestId: 'test-' . uniqid(),
            createdAt: new \DateTimeImmutable(),
        );
    }

    private function concreteRule(
        string $id,
        string $title,
        Severity|null $overrideSeverity = null,
    ): AbstractRule {
        return new class ($id, $title, $overrideSeverity) extends AbstractRule {
            public function __construct(
                private readonly string $ruleId,
                private readonly string $ruleTitle,
                private readonly Severity|null $overrideSeverity,
            ) {
            }

            public function id(): string
            {
                return $this->ruleId;
            }

            public function title(): string
            {
                return $this->ruleTitle;
            }

            public function severity(): Severity
            {
                return $this->overrideSeverity ?? parent::severity();
            }

            public function evaluate(SecurityRuntimeContext $context): array
            {
                return [$this->make('Something is wrong', '/test', ['key' => 'val'])];
            }
        };
    }
}
