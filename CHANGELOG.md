# Changelog

All notable changes to RuntimeShield are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

### Commits (26)

| Hash | Description |
|------|-------------|
| `edf7a99` | Merge pull request #1 from zaeem2396/feature/v0.1.0-foundation |
| `3b17f44` | docs(roadmap): add completion status labels to all phases and steps |
| `da4cce6` | fix: resolve PHPStan level-9 errors and CS after pre-check run |
| `4cfcb07` | chore: update root façade, phpunit config, and base unit test |
| `534669a` | test: add ServiceProvider integration tests via Orchestra Testbench |
| `058716e` | test: add RuntimeShieldManager unit tests |
| `67a8f8a` | test: add ConfigRepository unit tests |
| `223640c` | test: add RuntimeShieldConfig DTO unit tests |
| `fea0ed4` | feat(laravel/console): add InstallCommand (runtime-shield:install) |
| `c75eb96` | feat(laravel/middleware): add RuntimeShieldMiddleware |
| `8548235` | feat(laravel): register CLI commands in ServiceProvider boot() |
| `d5ee9c5` | feat(laravel): bind contracts to implementations in container |
| `a3353a3` | feat(laravel): add config merging and publishing to ServiceProvider |
| `61926b9` | feat(laravel): scaffold RuntimeShieldServiceProvider |
| `f620fc1` | feat(engine): implement RuntimeShieldEngine boot lifecycle |
| `478a93b` | feat(core): add force enable/disable to RuntimeShieldManager |
| `5057edf` | feat(core): add isEnabled() with config and sampling rate guard |
| `c27e2dd` | feat(core): add RuntimeShieldManager skeleton implementing ShieldContract |
| `3edbe8f` | feat(core): implement ConfigRepository |
| `eba3e0f` | feat(config): add runtime_shield.php package configuration |
| `2505dde` | feat(dto): add RuntimeShieldConfig immutable value object |
| `6c84b2b` | feat(support): add PackageVersion class with semver constants |
| `b259216` | feat(contracts): add EngineContract interface |
| `5f302b4` | feat(contracts): add ConfigRepositoryContract interface |
| `3f3ac45` | feat(contracts): add ShieldContract interface |
| `9ecfd06` | feat(skeleton): establish namespace directory structure |
| `4053248` | chore: initial project scaffold with CI pipelines |

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

### What's Next — v0.2.0 (Runtime Observation)

- Request signal capture: method, URL, headers, query, IP, size → `RequestSignal` DTO
- Response listener: status, headers, size, response time → `ResponseSignal` DTO
- Route and auth signals: `RouteSignal`, `AuthSignal`
- Full immutable DTO layer: Request, Response, Route, Auth

---

[v0.1.0]: https://github.com/zaeem2396/runtime-shield/releases/tag/v0.1.0
