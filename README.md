# RuntimeShield

[![Tests](https://github.com/zaeem2396/runtime-shield/actions/workflows/tests.yml/badge.svg)](https://github.com/zaeem2396/runtime-shield/actions/workflows/tests.yml)
[![Code Style](https://github.com/zaeem2396/runtime-shield/actions/workflows/code-style.yml/badge.svg)](https://github.com/zaeem2396/runtime-shield/actions/workflows/code-style.yml)
[![Static Analysis](https://github.com/zaeem2396/runtime-shield/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/zaeem2396/runtime-shield/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12%2F13-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

> Runtime security analysis and observation for PHP 8.2+ applications — with first-class Laravel 10 / 11 / 12 / 13 support.

RuntimeShield sits transparently in your HTTP middleware stack, captures request and response signals per lifecycle, and evaluates configurable security rules — with zero overhead when disabled.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | `^8.2`  |
| Laravel    | `^10.0`, `^11.0`, `^12.0`, or `^13.0` |

---

## Installation

```bash
composer require zaeem2396/runtime-shield
```

### Publish configuration

```bash
php artisan runtime-shield:install
```

This publishes `config/runtime_shield.php` to your application's config directory.

---

## Setup

### 1 — Register the middleware

Add `RuntimeShieldMiddleware` to your HTTP middleware stack.

**Laravel 11, 12, 13** (`bootstrap/app.php`):

```php
use RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(RuntimeShieldMiddleware::class);
})
```

**Laravel 10** (`app/Http/Kernel.php`):

```php
use RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware;

protected $middleware = [
    // ...
    RuntimeShieldMiddleware::class,
];
```

### 2 — Register the service provider (if not auto-discovered)

```php
// config/app.php
'providers' => [
    RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider::class,
],
```

> Laravel's package auto-discovery picks this up automatically if your `composer.json` includes the `extra.laravel.providers` key.

---

## Configuration

After publishing, edit `config/runtime_shield.php`:

```php
return [
    // Master switch — set false for absolute zero overhead
    'enabled' => env('RUNTIME_SHIELD_ENABLED', true),

    // Fraction of requests to process (0.0 = none, 1.0 = all)
    'sampling_rate' => env('RUNTIME_SHIELD_SAMPLING_RATE', 1.0),

    // Enable/disable rule groups
    'rules' => [
        'auth'       => true,
        'rate_limit' => true,
        'csrf'       => true,
        'validation' => true,
    ],

    // Engine performance tuning
    'performance' => [
        'async'      => false,
        'batch_size' => 50,
        'timeout_ms' => 100,
    ],
];
```

### Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `RUNTIME_SHIELD_ENABLED` | `true` | Master on/off switch |
| `RUNTIME_SHIELD_SAMPLING_RATE` | `1.0` | Request sampling (0.0–1.0) |

---

## Usage

### Resolving the manager

```php
use RuntimeShield\Contracts\ShieldContract;

$shield = app(ShieldContract::class);

if ($shield->isEnabled()) {
    // shield is active
}
```

### Reading captured signals

```php
use RuntimeShield\Contracts\Signal\SignalStoreContract;

$store = app(SignalStoreContract::class);

$request  = $store->getRequest();   // RequestSignal|null
$response = $store->getResponse();  // ResponseSignal|null
$route    = $store->getRoute();     // RouteSignal|null
$auth     = $store->getAuth();      // AuthSignal|null
```

### Captured data

**`RequestSignal`**

| Property | Type | Description |
|----------|------|-------------|
| `method` | `string` | Upper-cased HTTP method |
| `url` | `string` | Full URL including query string |
| `path` | `string` | URL path segment |
| `ip` | `string` | Client IP address |
| `headers` | `array<string, string>` | Normalized header map |
| `query` | `array<string, mixed>` | Decoded query parameters |
| `bodySize` | `int` | Request body size in bytes |
| `capturedAt` | `DateTimeImmutable` | Capture timestamp |

**`ResponseSignal`**

| Property | Type | Description |
|----------|------|-------------|
| `statusCode` | `int` | HTTP status code |
| `statusText` | `string` | HTTP reason phrase |
| `headers` | `array<string, string>` | Normalized header map |
| `bodySize` | `int` | Response body size in bytes |
| `responseTimeMs` | `float` | Wall-clock time in milliseconds |
| `capturedAt` | `DateTimeImmutable` | Capture timestamp |

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `runtime-shield:install` | Publish the configuration file |

---

## CI / GitHub Actions

Three independent workflows run on every push and pull request:

| Workflow | Trigger | What it checks |
|----------|---------|----------------|
| **Code Style** | push / PR | PHP CS Fixer (PSR-12 + PHP 8.2 migration rules) |
| **Static Analysis** | push / PR | PHPStan level 9 — zero errors |
| **Tests** | push / PR | PHPUnit — PHP 8.2/8.3/8.4 × Laravel 10/11/12/13 matrix |

All workflows use concurrency cancellation so only the latest run is active per branch.

---

## License

MIT © [RuntimeShield](https://github.com/zaeem2396/runtime-shield)
