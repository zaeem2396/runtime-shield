<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Signal\CustomSignalStore;

final class CustomSignalStoreTest extends TestCase
{
    // ------------------------------------------------------------------ store / get

    #[Test]
    public function store_and_get_returns_stored_data(): void
    {
        $store = new CustomSignalStore();
        $store->store('tenant', ['tenant_id' => 'acme']);

        $this->assertSame(['tenant_id' => 'acme'], $store->get('tenant'));
    }

    #[Test]
    public function get_returns_null_when_id_not_stored(): void
    {
        $store = new CustomSignalStore();

        $this->assertNull($store->get('nonexistent'));
    }

    #[Test]
    public function store_overwrites_existing_data_for_same_id(): void
    {
        $store = new CustomSignalStore();
        $store->store('meta', ['version' => 1]);
        $store->store('meta', ['version' => 2]);

        $this->assertSame(['version' => 2], $store->get('meta'));
    }

    // ------------------------------------------------------------------ has

    #[Test]
    public function has_returns_true_after_storing(): void
    {
        $store = new CustomSignalStore();
        $store->store('collector-a', ['key' => 'value']);

        $this->assertTrue($store->has('collector-a'));
    }

    #[Test]
    public function has_returns_false_when_not_stored(): void
    {
        $store = new CustomSignalStore();

        $this->assertFalse($store->has('missing'));
    }

    // ------------------------------------------------------------------ all

    #[Test]
    public function all_returns_all_stored_data(): void
    {
        $store = new CustomSignalStore();
        $store->store('a', ['x' => 1]);
        $store->store('b', ['y' => 2]);

        $all = $store->all();

        $this->assertCount(2, $all);
        $this->assertSame(['x' => 1], $all['a']);
        $this->assertSame(['y' => 2], $all['b']);
    }

    #[Test]
    public function all_returns_empty_array_on_fresh_store(): void
    {
        $store = new CustomSignalStore();

        $this->assertSame([], $store->all());
    }

    // ------------------------------------------------------------------ count

    #[Test]
    public function count_reflects_number_of_stored_entries(): void
    {
        $store = new CustomSignalStore();
        $this->assertSame(0, $store->count());

        $store->store('x', []);
        $this->assertSame(1, $store->count());

        $store->store('y', []);
        $this->assertSame(2, $store->count());
    }

    // ------------------------------------------------------------------ flush

    #[Test]
    public function flush_clears_all_data(): void
    {
        $store = new CustomSignalStore();
        $store->store('a', ['foo' => 'bar']);
        $store->store('b', ['baz' => 'qux']);

        $store->flush();

        $this->assertSame(0, $store->count());
        $this->assertSame([], $store->all());
    }

    #[Test]
    public function flush_on_empty_store_is_safe(): void
    {
        $store = new CustomSignalStore();
        $store->flush();

        $this->assertSame(0, $store->count());
    }

    #[Test]
    public function data_can_be_stored_again_after_flush(): void
    {
        $store = new CustomSignalStore();
        $store->store('x', ['before' => true]);
        $store->flush();
        $store->store('x', ['after' => true]);

        $this->assertSame(['after' => true], $store->get('x'));
    }

    // ------------------------------------------------------------------ complex data types

    #[Test]
    public function supports_nested_arrays_as_values(): void
    {
        $store = new CustomSignalStore();
        $store->store('complex', ['nested' => ['a' => 1, 'b' => [2, 3]]]);

        $result = $store->get('complex');

        $this->assertSame(['nested' => ['a' => 1, 'b' => [2, 3]]], $result);
    }

    #[Test]
    public function supports_multiple_collectors_independently(): void
    {
        $store = new CustomSignalStore();
        $store->store('collector-1', ['data1' => 'val1']);
        $store->store('collector-2', ['data2' => 'val2']);

        $this->assertSame(['data1' => 'val1'], $store->get('collector-1'));
        $this->assertSame(['data2' => 'val2'], $store->get('collector-2'));
    }
}
