<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Per-request in-memory store for all captured signals.
 * Implementations must be safe to reset between requests (Octane / workers).
 */
interface SignalStoreContract
{
    public function storeRequest(RequestSignal $signal): void;

    public function getRequest(): RequestSignal|null;

    public function storeResponse(ResponseSignal $signal): void;

    public function getResponse(): ResponseSignal|null;

    public function storeRoute(RouteSignal $signal): void;

    public function getRoute(): RouteSignal|null;

    public function storeAuth(AuthSignal $signal): void;

    public function getAuth(): AuthSignal|null;

    /** Clear all stored signals — call between requests in long-running processes. */
    public function reset(): void;
}
