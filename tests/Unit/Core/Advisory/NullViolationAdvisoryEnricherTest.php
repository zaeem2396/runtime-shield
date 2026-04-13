<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Advisory;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Advisory\NullViolationAdvisoryEnricher;
use RuntimeShield\DTO\Advisory\AdvisorySource;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class NullViolationAdvisoryEnricherTest extends TestCase
{
    #[Test]
    public function it_returns_collection_unchanged(): void
    {
        $v = new Violation('r', 'T', 'D', Severity::LOW);
        $c = new ViolationCollection([$v]);
        $enricher = new NullViolationAdvisoryEnricher();

        $out = $enricher->enrich($c, AdvisorySource::Cli);

        $this->assertSame($c, $out);
        $this->assertSame($v, $out->all()[0]);
    }
}
