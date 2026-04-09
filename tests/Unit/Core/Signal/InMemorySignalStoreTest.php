<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Signal;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Signal\InMemorySignalStore;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

final class InMemorySignalStoreTest extends TestCase
{
    private InMemorySignalStore $store;

    protected function setUp(): void
    {
        $this->store = new InMemorySignalStore();
    }

    #[Test]
    public function all_signals_are_null_before_any_storage(): void
    {
        $this->assertNull($this->store->getRequest());
        $this->assertNull($this->store->getResponse());
        $this->assertNull($this->store->getRoute());
        $this->assertNull($this->store->getAuth());
    }

    #[Test]
    public function it_stores_and_retrieves_a_request_signal(): void
    {
        $signal = $this->makeRequestSignal();
        $this->store->storeRequest($signal);

        $this->assertSame($signal, $this->store->getRequest());
    }

    #[Test]
    public function it_stores_and_retrieves_a_response_signal(): void
    {
        $signal = $this->makeResponseSignal();
        $this->store->storeResponse($signal);

        $this->assertSame($signal, $this->store->getResponse());
    }

    #[Test]
    public function it_stores_and_retrieves_a_route_signal(): void
    {
        $signal = new RouteSignal('home', '/', 'HomeController@index', 'HomeController', [], true);
        $this->store->storeRoute($signal);

        $this->assertSame($signal, $this->store->getRoute());
    }

    #[Test]
    public function it_stores_and_retrieves_an_auth_signal(): void
    {
        $signal = AuthSignal::unauthenticated('web');
        $this->store->storeAuth($signal);

        $this->assertSame($signal, $this->store->getAuth());
    }

    #[Test]
    public function it_overwrites_an_existing_signal_on_second_store(): void
    {
        $first  = $this->makeRequestSignal('GET');
        $second = $this->makeRequestSignal('POST');

        $this->store->storeRequest($first);
        $this->store->storeRequest($second);

        $this->assertSame($second, $this->store->getRequest());
    }

    #[Test]
    public function reset_clears_all_stored_signals(): void
    {
        $this->store->storeRequest($this->makeRequestSignal());
        $this->store->storeResponse($this->makeResponseSignal());
        $this->store->storeAuth(AuthSignal::unauthenticated());

        $this->store->reset();

        $this->assertNull($this->store->getRequest());
        $this->assertNull($this->store->getResponse());
        $this->assertNull($this->store->getRoute());
        $this->assertNull($this->store->getAuth());
    }

    // -------------------------------------------------------------------------

    private function makeRequestSignal(string $method = 'GET'): RequestSignal
    {
        return new RequestSignal($method, 'https://x.com', '/', '127.0.0.1', [], [], 0, new DateTimeImmutable());
    }

    private function makeResponseSignal(int $status = 200): ResponseSignal
    {
        return new ResponseSignal($status, 'OK', [], 0, 0.0, new DateTimeImmutable());
    }
}
