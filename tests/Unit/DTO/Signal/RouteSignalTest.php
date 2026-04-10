<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\DTO\Signal;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Signal\RouteSignal;

final class RouteSignalTest extends TestCase
{
    #[Test]
    public function it_stores_all_route_metadata(): void
    {
        $signal = new RouteSignal(
            name: 'users.show',
            uri: 'users/{id}',
            action: 'App\\Http\\Controllers\\UserController@show',
            controller: 'App\\Http\\Controllers\\UserController',
            middleware: ['auth', 'throttle:60,1'],
            hasNamedRoute: true,
        );

        $this->assertSame('users.show', $signal->name);
        $this->assertSame('users/{id}', $signal->uri);
        $this->assertSame('App\\Http\\Controllers\\UserController@show', $signal->action);
        $this->assertSame('App\\Http\\Controllers\\UserController', $signal->controller);
        $this->assertSame(['auth', 'throttle:60,1'], $signal->middleware);
        $this->assertTrue($signal->hasNamedRoute);
    }

    #[Test]
    public function it_supports_anonymous_routes_without_name(): void
    {
        $signal = new RouteSignal(
            name: '',
            uri: 'health',
            action: 'Closure',
            controller: '',
            middleware: [],
            hasNamedRoute: false,
        );

        $this->assertSame('', $signal->name);
        $this->assertFalse($signal->hasNamedRoute);
        $this->assertSame([], $signal->middleware);
    }
}
