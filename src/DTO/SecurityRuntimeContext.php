<?php

declare(strict_types=1);

namespace RuntimeShield\DTO;

use DateTimeImmutable;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\AuthSignal;
use RuntimeShield\DTO\Signal\ResponseSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Immutable, fully assembled security context for a single request lifecycle.
 *
 * Aggregates all four signal types (request, response, route, auth) into one
 * cohesive object that the rule engine and any downstream consumers can inspect
 * without touching the raw signal store.
 */
final class SecurityRuntimeContext
{
    public function __construct(
        public readonly string $requestId,
        public readonly DateTimeImmutable $createdAt,
        public readonly float $processingTimeMs = 0.0,
        public readonly RequestSignal|null $request = null,
        public readonly ResponseSignal|null $response = null,
        public readonly RouteSignal|null $route = null,
        public readonly AuthSignal|null $auth = null,
    ) {
    }

    public function hasRequest(): bool
    {
        return $this->request !== null;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function hasRoute(): bool
    {
        return $this->route !== null;
    }

    public function hasAuth(): bool
    {
        return $this->auth !== null;
    }

    /** Returns true only when all four signals have been captured. */
    public function isComplete(): bool
    {
        return $this->request !== null
            && $this->response !== null
            && $this->route !== null
            && $this->auth !== null;
    }

    /**
     * Serialize the context to a JSON-compatible array for logging or export.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'request_id'         => $this->requestId,
            'created_at'         => $this->createdAt->format(\DateTimeInterface::ATOM),
            'processing_time_ms' => $this->processingTimeMs,
            'is_complete'        => $this->isComplete(),
            'request'            => $this->request !== null ? [
                'method'      => $this->request->method,
                'url'         => $this->request->url,
                'path'        => $this->request->path,
                'ip'          => $this->request->ip,
                'body_size'   => $this->request->bodySize,
                'captured_at' => $this->request->capturedAt->format(\DateTimeInterface::ATOM),
            ] : null,
            'response'           => $this->response !== null ? [
                'status_code'      => $this->response->statusCode,
                'status_text'      => $this->response->statusText,
                'body_size'        => $this->response->bodySize,
                'response_time_ms' => $this->response->responseTimeMs,
                'captured_at'      => $this->response->capturedAt->format(\DateTimeInterface::ATOM),
            ] : null,
            'route'              => $this->route !== null ? [
                'name'            => $this->route->name,
                'uri'             => $this->route->uri,
                'action'          => $this->route->action,
                'controller'      => $this->route->controller,
                'middleware'      => $this->route->middleware,
                'has_named_route' => $this->route->hasNamedRoute,
            ] : null,
            'auth'               => $this->auth !== null ? [
                'is_authenticated' => $this->auth->isAuthenticated,
                'user_id'          => $this->auth->userId,
                'guard_name'       => $this->auth->guardName,
                'user_type'        => $this->auth->userType,
            ] : null,
        ];
    }
}
