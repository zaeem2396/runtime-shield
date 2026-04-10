<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class ViolationCollectionTest extends TestCase
{
    private function violation(Severity $severity = Severity::MEDIUM, string $id = 'rule-id'): Violation
    {
        return new Violation($id, 'Title', 'Desc', $severity);
    }

    #[Test]
    public function it_starts_empty(): void
    {
        $c = new ViolationCollection();

        $this->assertTrue($c->isEmpty());
        $this->assertSame(0, $c->count());
        $this->assertSame([], $c->all());
    }

    #[Test]
    public function it_holds_violations(): void
    {
        $c = new ViolationCollection([$this->violation(), $this->violation()]);

        $this->assertFalse($c->isEmpty());
        $this->assertSame(2, $c->count());
    }

    #[Test]
    public function by_severity_filters_correctly(): void
    {
        $c = new ViolationCollection([
            $this->violation(Severity::CRITICAL),
            $this->violation(Severity::HIGH),
            $this->violation(Severity::CRITICAL),
        ]);

        $this->assertCount(2, $c->critical());
        $this->assertCount(1, $c->high());
        $this->assertCount(0, $c->medium());
        $this->assertCount(0, $c->low());
    }

    #[Test]
    public function merge_combines_two_collections(): void
    {
        $a = new ViolationCollection([$this->violation(Severity::CRITICAL)]);
        $b = new ViolationCollection([$this->violation(Severity::HIGH)]);

        $merged = $a->merge($b);

        $this->assertSame(2, $merged->count());
        $this->assertCount(1, $merged->critical());
        $this->assertCount(1, $merged->high());
    }

    #[Test]
    public function sorted_returns_violations_ordered_by_priority(): void
    {
        $c = new ViolationCollection([
            $this->violation(Severity::LOW),
            $this->violation(Severity::CRITICAL),
            $this->violation(Severity::MEDIUM),
            $this->violation(Severity::HIGH),
        ]);

        $sorted = $c->sorted();

        $this->assertSame(Severity::CRITICAL, $sorted[0]->severity);
        $this->assertSame(Severity::HIGH, $sorted[1]->severity);
        $this->assertSame(Severity::MEDIUM, $sorted[2]->severity);
        $this->assertSame(Severity::LOW, $sorted[3]->severity);
    }

    #[Test]
    public function merge_does_not_mutate_originals(): void
    {
        $a = new ViolationCollection([$this->violation(Severity::CRITICAL)]);
        $b = new ViolationCollection([$this->violation(Severity::HIGH)]);

        $a->merge($b);

        $this->assertSame(1, $a->count());
        $this->assertSame(1, $b->count());
    }
}
