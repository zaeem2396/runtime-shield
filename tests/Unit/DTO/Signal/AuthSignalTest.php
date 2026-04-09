<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Signal\AuthSignal;

final class AuthSignalTest extends TestCase
{
    #[Test]
    public function it_stores_authenticated_state(): void
    {
        $signal = new AuthSignal(
            isAuthenticated: true,
            userId: '42',
            guardName: 'api',
            userType: 'App\\Models\\User',
        );

        $this->assertTrue($signal->isAuthenticated);
        $this->assertSame('42', $signal->userId);
        $this->assertSame('api', $signal->guardName);
        $this->assertSame('App\\Models\\User', $signal->userType);
    }

    #[Test]
    public function unauthenticated_factory_returns_correct_defaults(): void
    {
        $signal = AuthSignal::unauthenticated('web');

        $this->assertFalse($signal->isAuthenticated);
        $this->assertNull($signal->userId);
        $this->assertSame('web', $signal->guardName);
        $this->assertNull($signal->userType);
    }

    #[Test]
    public function unauthenticated_factory_accepts_null_guard(): void
    {
        $signal = AuthSignal::unauthenticated();

        $this->assertFalse($signal->isAuthenticated);
        $this->assertNull($signal->guardName);
    }
}
