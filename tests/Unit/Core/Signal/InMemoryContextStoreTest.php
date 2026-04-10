<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Signal;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Signal\InMemoryContextStore;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class InMemoryContextStoreTest extends TestCase
{
    public function test_get_returns_null_when_empty(): void
    {
        $store = new InMemoryContextStore();

        $this->assertNull($store->get());
    }

    public function test_has_returns_false_when_empty(): void
    {
        $store = new InMemoryContextStore();

        $this->assertFalse($store->has());
    }

    public function test_store_and_get_round_trip(): void
    {
        $store   = new InMemoryContextStore();
        $context = $this->makeContext();

        $store->store($context);

        $this->assertSame($context, $store->get());
    }

    public function test_has_returns_true_after_store(): void
    {
        $store = new InMemoryContextStore();
        $store->store($this->makeContext());

        $this->assertTrue($store->has());
    }

    public function test_store_overwrites_previous_context(): void
    {
        $store    = new InMemoryContextStore();
        $first    = $this->makeContext('first');
        $second   = $this->makeContext('second');

        $store->store($first);
        $store->store($second);

        $this->assertSame($second, $store->get());
    }

    public function test_reset_clears_stored_context(): void
    {
        $store = new InMemoryContextStore();
        $store->store($this->makeContext());
        $store->reset();

        $this->assertNull($store->get());
        $this->assertFalse($store->has());
    }

    // --- helper ---

    private function makeContext(string $id = 'ctx-id'): SecurityRuntimeContext
    {
        return new SecurityRuntimeContext(
            requestId: $id,
            createdAt: new DateTimeImmutable(),
        );
    }
}
