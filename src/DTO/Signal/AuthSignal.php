<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

/**
 * Immutable snapshot of the authentication state for the current request.
 */
final class AuthSignal
{
    public function __construct(
        public readonly bool $isAuthenticated,
        public readonly string|null $userId,
        public readonly string|null $guardName,
        public readonly string|null $userType,
    ) {}

    /**
     * Convenience factory for unauthenticated requests.
     * Avoids constructing a half-empty DTO at call sites.
     */
    public static function unauthenticated(string|null $guardName = null): self
    {
        return new self(
            isAuthenticated: false,
            userId: null,
            guardName: $guardName,
            userType: null,
        );
    }
}
