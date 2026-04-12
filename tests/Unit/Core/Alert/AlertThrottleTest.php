<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Alert\AlertThrottle;

final class AlertThrottleTest extends TestCase
{
    public function test_not_throttled_when_no_record_exists(): void
    {
        $throttle = new AlertThrottle(300);
        $this->assertFalse($throttle->isThrottled('rule-auth'));
    }

    public function test_throttled_immediately_after_record(): void
    {
        $throttle = new AlertThrottle(300);
        $throttle->record('rule-auth');
        $this->assertTrue($throttle->isThrottled('rule-auth'));
    }

    public function test_not_throttled_after_cooldown_expires(): void
    {
        $throttle = new AlertThrottle(0); // zero-second cooldown
        $throttle->record('rule-auth');
        // With cooldown=0, the condition is (elapsed < 0) which is always false
        $this->assertFalse($throttle->isThrottled('rule-auth'));
    }

    public function test_different_rules_are_independent(): void
    {
        $throttle = new AlertThrottle(300);
        $throttle->record('rule-a');
        $this->assertTrue($throttle->isThrottled('rule-a'));
        $this->assertFalse($throttle->isThrottled('rule-b'));
    }

    public function test_count_increments_on_record(): void
    {
        $throttle = new AlertThrottle(300);
        $this->assertSame(0, $throttle->count());
        $throttle->record('rule-a');
        $this->assertSame(1, $throttle->count());
        $throttle->record('rule-b');
        $this->assertSame(2, $throttle->count());
    }

    public function test_count_does_not_duplicate_on_re_record(): void
    {
        $throttle = new AlertThrottle(300);
        $throttle->record('rule-a');
        $throttle->record('rule-a');
        $this->assertSame(1, $throttle->count());
    }

    public function test_flush_clears_all_records(): void
    {
        $throttle = new AlertThrottle(300);
        $throttle->record('rule-a');
        $throttle->record('rule-b');
        $throttle->flush();
        $this->assertSame(0, $throttle->count());
        $this->assertFalse($throttle->isThrottled('rule-a'));
    }

    public function test_cooldown_seconds_accessor(): void
    {
        $throttle = new AlertThrottle(120);
        $this->assertSame(120, $throttle->cooldownSeconds());
    }
}
