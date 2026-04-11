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

### Reading the assembled context (v0.3.0+)

```php
use RuntimeShield\Contracts\Signal\RuntimeContextStoreContract;

$store   = app(RuntimeContextStoreContract::class);
$context = $store->get(); // SecurityRuntimeContext|null

if ($context !== null && $context->isComplete()) {
    echo $context->requestId;            // unique request identifier
    echo $context->processingTimeMs;     // total wall-clock time in ms
    echo $context->request->method;      // e.g. "GET"
    echo $context->response->statusCode; // e.g. 200
    echo $context->route->name;          // e.g. "dashboard"
    echo $context->auth->isAuthenticated ? 'auth' : 'guest';

    // JSON-serializable snapshot
    $payload = $context->toArray();
}
```

### Reading individual signals

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

## Rule Engine (v0.4.0+)

The Rule Engine evaluates every assembled `SecurityRuntimeContext` against a set of
security rules and returns a typed `ViolationCollection`.

### Built-in rules

| Rule | Severity | What it detects |
|------|----------|----------------|
| `PublicRouteWithoutAuthRule` | `CRITICAL` | Routes with no authentication middleware |
| `MissingRateLimitRule` | `MEDIUM` | Routes with no throttle / rate-limit middleware |
| `MissingCsrfRule` | `HIGH` | Mutable web routes (POST / PUT / PATCH / DELETE) missing CSRF middleware |
| `MissingValidationRule` | `LOW` | Mutable routes without any input-validation middleware (advisory) |
| `FileUploadValidationRule` | `MEDIUM` | POST routes whose URI suggests file upload with no upload-validation middleware |

### Evaluating violations in code

```php
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Contracts\Signal\RuntimeContextStoreContract;

$context = app(RuntimeContextStoreContract::class)->get();

if ($context !== null) {
    $violations = app(RuleEngineContract::class)->run($context);

    foreach ($violations->sorted() as $violation) {
        echo "[{$violation->severity->label()}] {$violation->title} — {$violation->route}\n";
    }
}
```

### Adding custom rules

```php
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

class MyCustomRule implements RuleContract
{
    public function id(): string      { return 'my-custom-rule'; }
    public function title(): string   { return 'My Custom Security Rule'; }
    public function severity(): Severity { return Severity::HIGH; }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        // inspect $context->route, $context->request, etc.
        return [];
    }
}

// In a service provider:
app(RuleRegistry::class)->register(new MyCustomRule());
```

---

## Security Scan Command (v0.4.0+)

Scan all registered routes for security violations without sending a real request:

```bash
php artisan runtime-shield:scan
```

**Example output:**

```
 RuntimeShield Security Scan
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Scanning 12 route(s)…

 ┌─────────────────────┬──────────────────────────────────────┬──────────┐
 │ Route / URI         │ Rule                                 │ Severity │
 ├─────────────────────┼──────────────────────────────────────┼──────────┤
 │ dashboard           │ Public Route Without Authentication  │ CRITICAL │
 │ contact             │ Missing CSRF Protection              │ HIGH     │
 │ api/users           │ Missing Rate Limit                   │ MEDIUM   │
 └─────────────────────┴──────────────────────────────────────┴──────────┘

  Found 3 violation(s)  (1 critical · 1 high · 1 medium · 0 low)
```

Output as JSON for CI pipelines:

```bash
php artisan runtime-shield:scan --format=json
```

The command exits with code `1` when any `CRITICAL` or `HIGH` violations are found,
making it suitable as a CI gate.

---

## Security Report Command (v0.5.0+)

Generate a comprehensive security report with violation sections, score, and grade:

```bash
php artisan runtime-shield:report
```

**Options:**

| Option | Description |
|--------|-------------|
| `--format=json` | Output the full report as JSON |
| `--save=<path>` | Write the JSON report to a file |

**Example output:**

```
 RuntimeShield Security Report
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Scanning 12 route(s)…
  Generated: 2026-04-10 14:00:00

🔴 CRITICAL — 1 violation(s)
──────────────────────────────────────────────────────
  dashboard
  ↳ Public Route Without Authentication

🟡 HIGH — 1 violation(s)
──────────────────────────────────────────────────────
  contact
  ↳ Missing CSRF Protection

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Security Score: 70/100   Grade: C
  Routes: 12  Exposed: 3
  Violations: 3  (1 critical · 1 high · 1 medium · 0 low)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## Route Protection Inspector (v0.5.0+)

Inspect every registered route's security coverage — auth, CSRF, and rate-limiting:

```bash
php artisan runtime-shield:routes
```

**Options:**

| Option | Description |
|--------|-------------|
| `--filter=exposed` | Show only routes with at least one missing protection |
| `--method=POST` | Filter rows by HTTP method |
| `--sort=risk` | Order rows by highest risk first |

**Example output:**

```
 RuntimeShield Route Protection Inspector
────────────────────────────────────────────────────────
 ┌────────┬───────────────────┬──────┬──────┬────────────┬──────────┐
 │ Method │ URI               │ Auth │ CSRF │ Rate Limit │ Status   │
 ├────────┼───────────────────┼──────┼──────┼────────────┼──────────┤
 │ GET    │ dashboard         │ ✘    │ —    │ ✘          │ CRITICAL │
 │ POST   │ contact           │ ✔    │ ✘    │ ✘          │ HIGH RISK│
 │ POST   │ api/users         │ ✔    │ —    │ ✔          │ SAFE     │
 └────────┴───────────────────┴──────┴──────┴────────────┴──────────┘

  3 route(s) shown   1 protected   2 exposed
```

---

## Security Score Command (v0.6.0+)

`runtime-shield:score` calculates a weighted security score (0–100) with a
per-category breakdown, letter grade, and a Unicode progress-bar table.

```bash
php artisan runtime-shield:score
```

```
 RuntimeShield Security Score
──────────────────────────────────────────────────

  Security Score:  72 / 100
  Grade:           C
  Total Violations: 5

  Category Breakdown:

 ─────────────────────────────────────────────────────────────────────────
  Category             Score      Coverage              Weight  Violations
 ─────────────────────────────────────────────────────────────────────────
  Authentication       40 / 100   ████████░░░░░░░░░░░░  30%     3
  CSRF Protection      80 / 100   ████████████████░░░░  25%     1
  Rate Limiting        90 / 100   ██████████████████░░  20%     1
  Input Validation    100 / 100   ████████████████████  15%     0
  File Upload Safety  100 / 100   ████████████████████  10%     0
 ─────────────────────────────────────────────────────────────────────────

  Highest risk area: Authentication — score 40/100
  → Routes protected by authentication middleware

  ✘ Categories below the passing threshold (75):
    · Authentication — score 40/100 (3 violation(s))
```

**Options:**

| Option | Description |
|--------|-------------|
| `--format=json` | Output the full score as JSON |

### Score as part of `runtime-shield:report`

The report command now includes the same per-category breakdown in its summary panel:

```bash
php artisan runtime-shield:report
```

### Quick score in scan

Add `--score` to any scan to see the weighted score at a glance:

```bash
php artisan runtime-shield:scan --score
```

---

## Security Score Weights

The five categories and their default weights are:

| Category | Key | Default Weight |
|----------|-----|----------------|
| Authentication | `auth` | 30% |
| CSRF Protection | `csrf` | 25% |
| Rate Limiting | `rate_limit` | 20% |
| Input Validation | `validation` | 15% |
| File Upload Safety | `file_upload` | 10% |

Override in `config/runtime_shield.php`:

```php
'scoring' => [
    'weights' => [
        'auth'        => 40,  // heavier auth focus
        'csrf'        => 20,
        'rate_limit'  => 20,
        'validation'  => 10,
        'file_upload' => 10,
    ],
],
```

### Score deductions per severity

| Severity | Points deducted per violation |
|----------|-------------------------------|
| CRITICAL | −20 |
| HIGH | −10 |
| MEDIUM | −5 |
| LOW | −2 |
| INFO | 0 |

Each category starts at 100 and is floored at 0. The overall score is the weighted average across all five categories.

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `runtime-shield:install` | Publish the configuration file |
| `runtime-shield:scan` | Scan all routes for security violations (table/JSON) |
| `runtime-shield:scan --score` | Scan and display the weighted security score |
| `runtime-shield:report` | Full security report with per-category score breakdown |
| `runtime-shield:routes` | Route protection inspector (auth · CSRF · rate-limit) |
| `runtime-shield:score` | Weighted security score with category breakdown (v0.6.0+) |

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
