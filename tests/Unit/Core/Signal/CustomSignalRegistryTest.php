<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Signal;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Signal\CustomSignalCollectorContract;
use RuntimeShield\Core\Signal\CustomSignalRegistry;

final class CustomSignalRegistryTest extends TestCase
{
    // ------------------------------------------------------------------ helpers

    private function makeCollector(string $id, array $data = []): CustomSignalCollectorContract
    {
        return new class ($id, $data) implements CustomSignalCollectorContract {
            public function __construct(
                private readonly string $collectorId,
                private readonly array $collectorData,
            ) {}

            public function id(): string { return $this->collectorId; }

            public function collect(Request $request): array { return $this->collectorData; }
        };
    }

    // ------------------------------------------------------------------ register / all

    #[Test]
    public function starts_empty(): void
    {
        $registry = new CustomSignalRegistry();

        $this->assertSame(0, $registry->count());
        $this->assertSame([], $registry->all());
    }

    #[Test]
    public function register_adds_collector(): void
    {
        $registry = new CustomSignalRegistry();
        $registry->register($this->makeCollector('tenant'));

        $this->assertSame(1, $registry->count());
    }

    #[Test]
    public function all_returns_all_registered_collectors_in_order(): void
    {
        $registry = new CustomSignalRegistry();
        $a = $this->makeCollector('a');
        $b = $this->makeCollector('b');
        $registry->register($a);
        $registry->register($b);

        $all = $registry->all();

        $this->assertCount(2, $all);
        $this->assertSame('a', $all[0]->id());
        $this->assertSame('b', $all[1]->id());
    }

    // ------------------------------------------------------------------ has / find

    #[Test]
    public function has_returns_true_when_collector_registered(): void
    {
        $registry = new CustomSignalRegistry();
        $registry->register($this->makeCollector('meta'));

        $this->assertTrue($registry->has('meta'));
    }

    #[Test]
    public function has_returns_false_when_not_registered(): void
    {
        $registry = new CustomSignalRegistry();

        $this->assertFalse($registry->has('ghost'));
    }

    #[Test]
    public function find_returns_collector_by_id(): void
    {
        $registry = new CustomSignalRegistry();
        $collector = $this->makeCollector('geo');
        $registry->register($collector);

        $this->assertSame($collector, $registry->find('geo'));
    }

    #[Test]
    public function find_returns_null_when_not_found(): void
    {
        $registry = new CustomSignalRegistry();

        $this->assertNull($registry->find('missing'));
    }

    // ------------------------------------------------------------------ unregister

    #[Test]
    public function unregister_removes_collector_by_id(): void
    {
        $registry = new CustomSignalRegistry();
        $registry->register($this->makeCollector('remove-me'));
        $registry->register($this->makeCollector('keep-me'));

        $result = $registry->unregister('remove-me');

        $this->assertTrue($result);
        $this->assertSame(1, $registry->count());
        $this->assertNull($registry->find('remove-me'));
        $this->assertNotNull($registry->find('keep-me'));
    }

    #[Test]
    public function unregister_returns_false_when_id_not_found(): void
    {
        $registry = new CustomSignalRegistry();
        $registry->register($this->makeCollector('keep'));

        $this->assertFalse($registry->unregister('ghost'));
        $this->assertSame(1, $registry->count());
    }

    // ------------------------------------------------------------------ count

    #[Test]
    public function count_reflects_registered_collectors(): void
    {
        $registry = new CustomSignalRegistry();
        $this->assertSame(0, $registry->count());

        $registry->register($this->makeCollector('x'));
        $this->assertSame(1, $registry->count());

        $registry->register($this->makeCollector('y'));
        $this->assertSame(2, $registry->count());

        $registry->unregister('x');
        $this->assertSame(1, $registry->count());
    }
}
