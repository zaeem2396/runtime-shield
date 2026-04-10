<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Rule;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Rule\Severity;

final class SeverityTest extends TestCase
{
    #[Test]
    public function it_has_all_five_cases(): void
    {
        $cases = Severity::cases();
        $values = array_map(static fn (Severity $s): string => $s->value, $cases);

        $this->assertContains('critical', $values);
        $this->assertContains('high', $values);
        $this->assertContains('medium', $values);
        $this->assertContains('low', $values);
        $this->assertContains('info', $values);
    }

    #[Test]
    public function it_returns_uppercase_label(): void
    {
        $this->assertSame('CRITICAL', Severity::CRITICAL->label());
        $this->assertSame('HIGH', Severity::HIGH->label());
        $this->assertSame('MEDIUM', Severity::MEDIUM->label());
        $this->assertSame('LOW', Severity::LOW->label());
        $this->assertSame('INFO', Severity::INFO->label());
    }

    #[Test]
    public function it_returns_color_for_each_severity(): void
    {
        $this->assertSame('red', Severity::CRITICAL->color());
        $this->assertSame('yellow', Severity::HIGH->color());
        $this->assertSame('cyan', Severity::MEDIUM->color());
        $this->assertSame('blue', Severity::LOW->color());
        $this->assertSame('white', Severity::INFO->color());
    }

    #[Test]
    public function critical_has_lowest_priority_number(): void
    {
        $this->assertLessThan(Severity::HIGH->priority(), Severity::CRITICAL->priority());
        $this->assertLessThan(Severity::MEDIUM->priority(), Severity::HIGH->priority());
        $this->assertLessThan(Severity::LOW->priority(), Severity::MEDIUM->priority());
        $this->assertLessThan(Severity::INFO->priority(), Severity::LOW->priority());
    }

    #[Test]
    public function it_can_be_created_from_string_value(): void
    {
        $this->assertSame(Severity::CRITICAL, Severity::from('critical'));
        $this->assertSame(Severity::HIGH, Severity::from('high'));
    }
}
