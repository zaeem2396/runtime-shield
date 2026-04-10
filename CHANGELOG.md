# Changelog

All notable changes to RuntimeShield are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [v0.2.0] — 2026-04-10 — Runtime Observation

### Overview

Introduces the complete HTTP signal capture layer. Every inbound request and
outbound response is recorded as an immutable DTO inside an in-memory signal
store per request lifecycle, with zero overhead when the shield is disabled.
Also extends Laravel support to versions 12 and 13 and renames the Packagist
slug to `zaeem2396/runtime-shield`.

---

### Added

#### DTOs — `RuntimeShield\DTO\Signal`

- `RequestSignal` — immutable snapshot of an inbound HTTP request
  - Properties: `method`, `url`, `path`, `ip`, `headers` (`array<string, mixed>`), `query` (`array<string, mixed>`), `bodySize` (`int`), `capturedAt` (`DateTimeImmutable`)
  - `fromArray(array $data): self` factory with full type narrowing (PHPStan level 9 safe)
- `ResponseSignal` — immutable snapshot of an outbound HTTP response
  - Properties: `statusCode` (`int`), `statusText` (`string`), `headers` (`array<string, mixed>`), `bodySize` (`int`), `responseTimeMs` (`float`), `capturedAt` (`DateTimeImmutable`)
  - `fromArray(array $data): self` factory with full type narrowing
- `RouteSignal` — immutable route metadata snapshot
  - Properties: `name`, `uri`, `action`, `controller`, `middleware` (`list<string>`), `hasNamedRoute` (`bool`)
- `AuthSignal` — immutable authentication state snapshot
  - Properties: `isAuthenticated` (`bool`), `userId` (`string|null`), `guardName` (`string`), `userType` (`string|null`)
  - `unauthenticated(string $guard): self` named factory

#### Contracts — `RuntimeShield\Contracts\Signal`

- `SignalStoreContract` — per-request signal storage with `setRequest()`, `setResponse()`, `setRoute()`, `setAuth()`, corresponding getters, and `reset()`
- `RequestCapturerContract` — `capture(Request $request): RequestSignal`
- `ResponseCapturerContract` — `capture(Response $response, float $startTimeMs): ResponseSignal`
- `RouteCollectorContract` — `collect(Request $request): RouteSignal`
- `AuthCollectorContract` — `collect(): AuthSignal`

#### Core — `RuntimeShield\Core\Signal`

- `InMemorySignalStore` — `SignalStoreContract` implementation; holds one signal of each type per request lifecycle; `reset()` clears state for Octane / long-running workers
- `SignalNormalizer` — framework-agnostic converter; delegates raw data arrays to `RequestSignal::fromArray()` and `ResponseSignal::fromArray()`

#### Laravel Adapters — `RuntimeShield\Laravel\Signal`

- `RequestCapturer` — extracts and normalizes data from `Illuminate\Http\Request`; normalizes headers to `array<string, string>`
- `ResponseCapturer` — extracts data from `Symfony\Component\HttpFoundation\Response`; computes `responseTimeMs` from a start-time float passed at capture time
- `RouteSignalCollector` — pulls route metadata from `Illuminate\Http\Request`; normalizes middleware list to `list<string>`
- `AuthSignalCollector` — inspects auth state via `Illuminate\Contracts\Auth\Factory`; `resolveUserId()` helper safely narrows the `mixed` identifier from `getAuthIdentifier()` (PHPStan level 9 safe)

#### Middleware — updated

- `RuntimeShieldMiddleware` — now injects `SignalStoreContract`, `RequestCapturerContract`, `ResponseCapturerContract` via constructor
  - `handle()` — records `startTimeMs` and stores `RequestSignal` on every sampled request
  - `terminate()` — stores `ResponseSignal` after the response is sent (non-blocking)

#### Service Provider — updated

- `RuntimeShieldServiceProvider` — registers five new singletons:

  | Contract | Implementation |
  |----------|---------------|
  | `SignalStoreContract` | `InMemorySignalStore` |
  | `RequestCapturerContract` | `RequestCapturer` |
  | `ResponseCapturerContract` | `ResponseCapturer` |
  | `RouteCollectorContract` | `RouteSignalCollector` |
  | `AuthCollectorContract` | `AuthSignalCollector` |

#### Tests

- 80 tests, 175 assertions — all passing
- `RequestSignalTest` — DTO construction, `fromArray()`, defaults, type coercion
- `ResponseSignalTest` — DTO construction, `fromArray()`, defaults, type coercion
- `RouteSignalTest` / `AuthSignalTest` — DTO fields and `unauthenticated()` factory
- `InMemorySignalStoreTest` — store / retrieve all signal types, overwrite, `reset()`
- `SignalNormalizerTest` — `normalizeRequest()` and `normalizeResponse()` with defaults and coercion
- `RequestCapturerTest` / `ResponseCapturerTest` — Laravel adapter data extraction

---

### Changed

- **Package name** — Packagist slug renamed from `runtime-shield/runtime-shield` to `zaeem2396/runtime-shield`; update your `composer require` accordingly
- **Laravel support** — extended to `^12.0` and `^13.0`; `illuminate/support` constraint is now `^10.0|^11.0|^12.0|^13.0`
- **PHPUnit** — dev constraint broadened to `^10.5|^11.0|^12.0` to match testbench requirements per Laravel version
- **CI test matrix** — five new entries added (Laravel 12 × PHP 8.2/8.3/8.4, Laravel 13 × PHP 8.3/8.4) with per-entry `phpunit` version pinning

---

### Installation

```bash
composer require zaeem2396/runtime-shield
php artisan runtime-shield:install
```

Register the middleware:

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
    RuntimeShieldMiddleware::class,
];
```

---

### Requirements

- PHP `^8.2`
- Laravel `^10.0`, `^11.0`, `^12.0`, or `^13.0`

---

### What's Next — v0.3.0 (Signal Engine)

- `SecurityRuntimeContext` — builder-pattern immutable context assembled from all four signals
- Normalization layer — unified DTO conversion pipeline
- In-memory store per request lifecycle (Octane-safe)
- Percentage-based request sampling

---

## [v0.1.0] — 2026-04-09 — Foundation

### Overview

Inaugural release. Establishes the complete package architecture with a
framework-agnostic core, a first-class Laravel integration layer, and a
full CI pipeline. Every file is statically analysed at PHPStan level 9
and formatted with PHP CS Fixer.

---

### Added

#### Contracts
- `ShieldContract` — primary interface for the shield manager; declares `isEnabled()` and `version()`
- `ConfigRepositoryContract` — framework-agnostic config abstraction; declares `get()`, `all()`, `isEnabled()`, `samplingRate()`
- `EngineContract` — request-lifecycle interface; declares `boot()` and `isBooted()`

#### DTOs
- `RuntimeShieldConfig` — immutable value object for resolved package config
  - `fromArray(array $config): self` factory for raw config arrays
  - `withEnabled(bool): self` and `withSamplingRate(float): self` non-destructive wither methods

#### Core
- `ConfigRepository` — `ConfigRepositoryContract` implementation backed by `RuntimeShieldConfig`; works standalone without a Laravel container
- `RuntimeShieldManager` — main entry point implementing `ShieldContract`
  - Config-driven `isEnabled()` gate with zero-overhead short-circuit when disabled
  - Probabilistic sampling via `sampling_rate` (0.0–1.0) using `mt_rand()`
  - Runtime `disable()` / `enable()` override for test isolation

#### Engine
- `RuntimeShieldEngine` — idempotent `boot()` lifecycle, `isBooted()` guard, `reset()` for Octane / long-running workers

#### Support
- `PackageVersion` — single source of truth for the semver string; exposes `VERSION`, `MAJOR`, `MINOR`, `PATCH` constants and `compareTo()` helper

#### Configuration
- `config/runtime_shield.php` — four sections driven by environment variables

  | Key | Env var | Default |
  |-----|---------|---------|
  | `enabled` | `RUNTIME_SHIELD_ENABLED` | `true` |
  | `sampling_rate` | `RUNTIME_SHIELD_SAMPLING_RATE` | `1.0` |
  | `rules` | — | `auth`, `rate_limit`, `csrf`, `validation` all `true` |
  | `performance` | — | `async=false`, `batch_size=50`, `timeout_ms=100` |

#### Laravel Integration
- `RuntimeShieldServiceProvider`
  - `mergeConfigFrom()` ensures package defaults are always present
  - Publishes config under the `runtime-shield-config` tag
  - Registers three container singletons: `ConfigRepositoryContract`, `RuntimeShieldManager` (aliased as `ShieldContract`), `EngineContract`
  - Registers Artisan commands behind `runningInConsole()` guard
- `RuntimeShieldMiddleware` — HTTP middleware; single passthrough call when disabled, `engine->boot()` on the hot path when enabled
- `InstallCommand` (`runtime-shield:install`) — publishes config and prints onboarding instructions

#### Tests
- 39 tests, 70 assertions — all passing
- `RuntimeShieldConfigTest` — 7 cases: construction, defaults, type coercion, immutability
- `ConfigRepositoryTest` — 11 cases: enabled state, sampling rate, key access, `all()`, `dto()`
- `RuntimeShieldManagerTest` — 10 cases: enabled/disabled paths, sampling edge cases, force enable/disable, version
- `RuntimeShieldServiceProviderTest` — 8 integration cases via Orchestra Testbench against a real Laravel container

#### CI / Tooling
- GitHub Actions — three independent workflows, each with concurrency cancellation:
  - **Code Style** (`code-style.yml`) — PHP CS Fixer dry-run on PHP 8.2
  - **Static Analysis** (`static-analysis.yml`) — PHPStan level 9 on PHP 8.2
  - **Tests** (`tests.yml`) — PHPUnit matrix across PHP 8.2 / 8.3 / 8.4 × Laravel 10 / 11
- `composer.json` scripts: `test`, `test:coverage`, `analyse`, `format`, `format:test`, `pre-check`

#### Documentation
- `docs/roadmap.md` — full build execution guide (v0.1.0 → v1.2.0) with 🟢 / 🟡 / 🔴 status labels

---

### Installation

```bash
composer require runtime-shield/runtime-shield
php artisan runtime-shield:install
```

Register the middleware in your HTTP kernel or middleware stack:

```php
\RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware::class,
```

---

### Requirements

- PHP `^8.2`
- Laravel `^10.0` or `^11.0`

---

[v0.2.0]: https://github.com/zaeem2396/runtime-shield/releases/tag/v0.2.0
[v0.1.0]: https://github.com/zaeem2396/runtime-shield/releases/tag/v0.1.0
