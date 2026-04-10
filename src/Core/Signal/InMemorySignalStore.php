<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Signal;

use RuntimeShield\Contracts\Signal\SignalStoreContract;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Ephemeral in-memory store that holds all four signal types for the
 * duration of a single request lifecycle.
 */
final class InMemorySignalStore implements SignalStoreContract
{
    private RequestSignal|null $request = null;

    private ResponseSignal|null $response = null;

    private RouteSignal|null $route = null;

    private AuthSignal|null $auth = null;

    public function storeRequest(RequestSignal $signal): void
    {
        $this->request = $signal;
    }

    public function getRequest(): RequestSignal|null
    {
        return $this->request;
    }

    public function storeResponse(ResponseSignal $signal): void
    {
        $this->response = $signal;
    }

    public function getResponse(): ResponseSignal|null
    {
        return $this->response;
    }

    public function storeRoute(RouteSignal $signal): void
    {
        $this->route = $signal;
    }

    public function getRoute(): RouteSignal|null
    {
        return $this->route;
    }

    public function storeAuth(AuthSignal $signal): void
    {
        $this->auth = $signal;
    }

    public function getAuth(): AuthSignal|null
    {
        return $this->auth;
    }

    public function reset(): void
    {
        $this->request = null;
        $this->response = null;
        $this->route = null;
        $this->auth = null;
    }
}
