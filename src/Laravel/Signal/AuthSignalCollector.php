<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Signal;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use RuntimeShield\Contracts\Signal\AuthCollectorContract;
use RuntimeShield\DTO\Signal\AuthSignal;

/**
 * Inspects the current Laravel authentication state via the Auth factory.
 * Always returns an AuthSignal — unauthenticated state uses the named factory.
 */
final class AuthSignalCollector implements AuthCollectorContract
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly string $guard = 'web',
    ) {}

    public function collect(): AuthSignal
    {
        $guard = $this->auth->guard($this->guard);
        $user  = $guard->user();

        return new AuthSignal(
            isAuthenticated: $guard->check(),
            userId: $user !== null ? (string) $user->getAuthIdentifier() : null,
            guardName: $this->guard,
            userType: $user !== null ? $user::class : null,
        );
    }
}
