# RuntimeShield

**Runtime security and observability for Laravel — scan routes, score risk, and catch misconfigurations before production.**

[![Tests](https://github.com/zaeem2396/runtime-shield/actions/workflows/tests.yml/badge.svg)](https://github.com/zaeem2396/runtime-shield/actions/workflows/tests.yml)
[![Code Style](https://github.com/zaeem2396/runtime-shield/actions/workflows/code-style.yml/badge.svg)](https://github.com/zaeem2396/runtime-shield/actions/workflows/code-style.yml)
[![Static Analysis](https://github.com/zaeem2396/runtime-shield/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/zaeem2396/runtime-shield/actions/workflows/static-analysis.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12%2F13-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

PHP **8.2+**. Laravel **10 / 11 / 12 / 13**. Middleware captures request + response signals, runs a **rule engine** (batched, timeout-bounded, optionally async), and can **alert** on live traffic. **Disabled = zero hot-path overhead.**

---

### Try in 30 seconds

```bash
composer require zaeem2396/runtime-shield
php artisan runtime-shield:install
php artisan runtime-shield:scan
```

**Example — what you see on a risky app:**

```text
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

Then go deeper:

```bash
php artisan runtime-shield:report
php artisan runtime-shield:score
```

---

## Why this exists

- **Static analysis** and route files tell you what *might* run. **RuntimeShield** reasons about **middleware, auth, CSRF, throttles, and responses** in the shape Laravel actually executes.
- The gap: issues like **public POSTs without auth**, **missing rate limits**, **CSRF gaps**, **risky uploads**, and **runtime response anomalies** are easy to miss in code review alone.
- Outcome: a **fast CLI** for CI and local dev, plus an optional **HTTP path** for live observation — without replacing Laravel’s own security features.

---

## Features

**Detection (examples)**

- Missing or weak **authentication** on sensitive routes  
- **CSRF** gaps on mutable web routes  
- **Rate limiting** / throttle coverage  
- **Validation** middleware hints  
- **File upload** surface heuristics  
- **Runtime** signals: error exposure, auth brute-force patterns, security headers, response anomalies (slow/huge/malformed patterns)

**Operations**

- **CLI**: `scan`, `report`, `score`, `routes`, `bench`, `sampling`, `alerts`, `plugins`  
- **Weighted security score** (0–100) + grade + per-category breakdown  
- **Alerts**: log, webhook, Slack, mail — throttled, optional async  
- **Performance**: sampling (incl. per-`APP_ENV`), batched rules, `timeout_ms`, optional **async** evaluation off the request path  
- **DX** (v1.2+): `dashboard`, versioned `export`, `ci` gate  

**Extensibility**

- Custom **rules**, **signal collectors**, **plugins**, **events** — all config-driven  

**Optional AI**

- OpenAI-compatible **advisory** text on violations — **scores stay deterministic**; toggle off with `--no-ai` or config  

---

## Table of contents

- [Why this exists](#why-this-exists)  
- [Features](#features)  
- [Requirements](#requirements)  
- [Installation](#installation)  
- [Setup](#setup)  
- [Quick usage](#quick-usage)  
- [Rule engine overview](#rule-engine-overview)  
- [Security scan output](#security-scan-output)  
- [Security score](#security-score)  
- [Performance notes](#performance-notes)  
- [Extensibility](#extensibility)  
- [AI advisory (optional)](#ai-advisory-optional)  
- [CLI commands summary](#cli-commands-summary)  
- [Developer experience](#developer-experience)  
- [CI and GitHub Actions](#ci-and-github-actions)  
- [Contributing](#contributing)  
- [License](#license)  

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | `^8.2` |
| Laravel    | `^10.0`, `^11.0`, `^12.0`, or `^13.0` |

---

## Installation

```bash
composer require zaeem2396/runtime-shield
```

Publish config:

```bash
php artisan runtime-shield:install
```

Creates `config/runtime_shield.php`.

---

## Setup

**1. Middleware** — append `RuntimeShieldMiddleware` to your HTTP stack.

Laravel **11 / 12 / 13** — `bootstrap/app.php`:

```php
use RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RuntimeShieldMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

> Already using `->withMiddleware()`? Add `append(RuntimeShieldMiddleware::class)` **inside the same callback** — avoid a second `withMiddleware` block.

Laravel **10** — `app/Http/Kernel.php`:

```php
use RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware;

protected $middleware = [
    // ...
    RuntimeShieldMiddleware::class,
];
```

**2. Service provider** — auto-discovered via `composer.json` `extra.laravel.providers`. If you disabled discovery:

```php
// config/app.php
'providers' => [
    RuntimeShield\Laravel\Providers\RuntimeShieldServiceProvider::class,
],
```

---

## Quick usage

**Scan every route** (synthetic request/route context — fast, CI-friendly):

```bash
php artisan runtime-shield:scan
```

**JSON** (automation, dashboards):

```bash
php artisan runtime-shield:scan --format=json
```

**Full report** + score summary:

```bash
php artisan runtime-shield:report
```

**Example — report header + score strip:**

```text
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

**Score only:**

```bash
php artisan runtime-shield:score
php artisan runtime-shield:scan --score
```

**AI on by default in config?** Use `--no-ai` on `scan` / `report` for a deterministic, instant run.

---

## Rule engine overview

1. **Signals** — middleware + collectors assemble a `SecurityRuntimeContext` (request, route, auth, response when available).  
2. **Rules** — each rule inspects that context and returns zero or more `Violation` objects with deterministic severity.  
3. **Engine** — rules run in **batches** under a **time budget**; optional **async** mode dispatches work to the queue so the HTTP response is not blocked.  
4. **Outputs** — CLI aggregates violations across routes; live traffic can trigger **alerts** when enabled.

**Built-in rules (summary)**

| Rule | Severity | Detects |
|------|----------|---------|
| `PublicRouteWithoutAuthRule` | CRITICAL | Sensitive routes without auth middleware |
| `MissingCsrfRule` | HIGH | Mutable web routes without CSRF middleware |
| `MissingRateLimitRule` | MEDIUM | Missing throttle / rate limit |
| `MissingValidationRule` | LOW | Mutable routes without validation middleware (advisory) |
| `FileUploadValidationRule` | MEDIUM | Upload-shaped POSTs without upload validation middleware |
| `ErrorExposureRule` | HIGH | 5xx responses suggesting debug / exception leakage |
| `BruteForcePatternRule` | HIGH | 401 auth-endpoint failures without throttling |
| `MissingSecurityHeadersRule` | MEDIUM | Baseline headers (CSP, X-Frame-Options, HSTS on HTTPS) |
| `ResponseAnomalyRule` | MEDIUM | Very slow, oversized, or malformed response patterns |

> **CLI vs HTTP:** `scan` / `report` synthesize **route + request** contexts. Rules that need a **real response** fire on the **middleware** path. Plan scans accordingly.

---

## Security scan output

```bash
php artisan runtime-shield:scan
```

**Table example:**

```text
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

**Exit codes:** `runtime-shield:scan` exits **1** when any **CRITICAL** or **HIGH** violation is present — suitable as a CI gate alongside `runtime-shield:ci`.

---

## Security score

Weighted **0–100** score, **letter grade**, and per-category rows (auth, CSRF, rate limit, validation, file upload). Deductions per violation severity are applied **per category**; overall score is a **weighted blend**.

```bash
php artisan runtime-shield:score
```

**Example:**

```text
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

| Category | Config key | Default weight |
|----------|------------|----------------|
| Authentication | `auth` | 30% |
| CSRF | `csrf` | 25% |
| Rate limiting | `rate_limit` | 20% |
| Validation | `validation` | 15% |
| File upload | `file_upload` | 10% |

Override weights and thresholds in `config/runtime_shield.php` under `scoring`.

```bash
php artisan runtime-shield:score --format=json
```

---

## Performance notes

- **`enabled => false`** — early exit; `SignalPipelineContract` resolves to a **no-op** pipeline → **zero allocations** on the hot path.  
- **Sampling** — global `sampling_rate` plus optional per-`APP_ENV` overrides (`sampling.env_rates`).  
- **Batches + `timeout_ms`** — cap how long rule evaluation may run.  
- **`async`** — queue-backed evaluation after the response is sent (when enabled).  
- **`runtime-shield:bench`** — always measures **synchronous** rule cost (bypasses async wrapper).  

```bash
php artisan runtime-shield:sampling
php artisan runtime-shield:bench
php artisan runtime-shield:bench --iterations=5 --format=json
```

---

## Extensibility

Register in `config/runtime_shield.php` under `extensibility` — no package forks required.

**Custom rules** — `RuleContract` / `AbstractRule`:

```php
'extensibility' => [
    'rules' => [
        App\Rules\MyCustomRule::class,
    ],
],
```

**Signal collectors** — `CustomSignalCollectorContract` for app-specific context (tenant id, flags, etc.).

**Plugins** — bundle rules + collectors + boot logic (`AbstractPlugin`).

**Events** — `BeforeScanEvent`, `AfterScanEvent`, `ViolationDetectedEvent` (disable via `events.enabled` when unused).

**Runtime registration** — `RuleRegistrar` for disable/replace of built-ins.

```bash
php artisan runtime-shield:plugins
```

---

## AI advisory (optional)

OpenAI-compatible Chat Completions can attach an **`advisory`** object per violation (`summary`, `impact`, `remediation`, optional triage hint, confidence). **Rule severity and numeric scores are unchanged.**

| Surface | Behaviour |
|---------|-----------|
| CLI (`scan`, `report`) | Enrichment when `ai.enabled` + API key set; **`--no-ai`** skips |
| HTTP / alerts | **Off by default** — set `ai.enrich_http_requests` only if you accept latency/cost |

**Slow CLI with AI?** Large violation counts batch to OpenAI — use **`--no-ai`** for an immediate deterministic run. Spelling: **`runtime-shield:scan`** (shield, not `sheild`).

```php
// config/runtime_shield.php (excerpt)
'ai' => [
    'enabled' => env('RUNTIME_SHIELD_AI_ENABLED', false),
    'enrich_http_requests' => env('RUNTIME_SHIELD_AI_ENRICH_HTTP', false),
    'api_key' => env('RUNTIME_SHIELD_AI_API_KEY', ''),
    'base_url' => env('RUNTIME_SHIELD_AI_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('RUNTIME_SHIELD_AI_MODEL', 'gpt-4o-mini'),
    'timeout_ms' => (int) env('RUNTIME_SHIELD_AI_TIMEOUT_MS', 60_000),
    'max_tokens' => (int) env('RUNTIME_SHIELD_AI_MAX_TOKENS', 4096),
    'batch_size' => (int) env('RUNTIME_SHIELD_AI_BATCH_SIZE', 20),
],
```

Bind `ViolationAdvisoryEnricherContract` to swap implementations (custom model, air-gapped stub, etc.).

---

## CLI commands summary

| Command | What it does |
|---------|----------------|
| `runtime-shield:install` | Publish `config/runtime_shield.php` |
| `runtime-shield:scan` | Route scan — table / JSON; exits **1** on CRITICAL/HIGH |
| `runtime-shield:scan --no-ai` | Skip AI enrichment for this run |
| `runtime-shield:scan --score` | Scan + print weighted score |
| `runtime-shield:report` | Full report + score block |
| `runtime-shield:report --no-ai` | Skip AI enrichment for this run |
| `runtime-shield:routes` | Auth · CSRF · rate-limit matrix per route |
| `runtime-shield:score` | Score + category table / JSON |
| `runtime-shield:bench` | Per-route timing (sync engine) |
| `runtime-shield:sampling` | Show effective sampler + rate |
| `runtime-shield:alerts` | Alert channel status |
| `runtime-shield:plugins` | List registered plugins |
| `runtime-shield:dashboard` | Local debug: config, rule count, recent metrics (`--format=json`, `--samples=`) |
| `runtime-shield:export` | Versioned JSON artifact: `score` or `report`; optional `--output=` |
| `runtime-shield:ci` | CI gate — non-zero exit if score / severity budgets fail |

---

## Developer experience

**Dashboard** — what’s enabled, sampling, async flag, rule count, and recent middleware ring-buffer rows (needs traffic through middleware for samples):

```bash
php artisan runtime-shield:dashboard
php artisan runtime-shield:dashboard --format=json --samples=8
```

**Export** — stable envelope for tooling (`export_schema_version`, `package_version`, `artifact`, `generated_at`, `data`):

```bash
php artisan runtime-shield:export score
php artisan runtime-shield:export report --output=storage/app/runtime-shield-report.json
```

**CI gate** — separate from scan’s CRITICAL/HIGH exit; tune **minimum score** and **severity budgets**:

```bash
php artisan runtime-shield:ci
php artisan runtime-shield:ci --min-score=80 --max-critical=0 --max-high=5
```

Defaults also read from `runtime_shield.dx` in config (see published file).

---

## CI and GitHub Actions

**Project workflows** (this repo) — on every push / PR:

| Workflow | What runs |
|----------|-----------|
| Code style | PHP CS Fixer |
| Static analysis | PHPStan level 9 |
| Tests | PHPUnit — PHP 8.2 / 8.3 / 8.4 × Laravel 10–13 matrix |

**Your app — example gate step:**

```yaml
- name: Install dependencies
  run: composer install --no-interaction --prefer-dist

- name: RuntimeShield CI gate
  run: php artisan runtime-shield:ci --min-score=75 --max-critical=0
```

**Alternative — fail on high-severity scan findings:**

```yaml
- name: RuntimeShield scan (fail on critical/high)
  run: php artisan runtime-shield:scan --no-ai
```

---

## Contributing

Issues and pull requests are welcome on [GitHub](https://github.com/zaeem2396/runtime-shield). Before opening a PR, run **`composer run pre-check`** (format, analyse, test).

---

## License

MIT © [RuntimeShield](https://github.com/zaeem2396/runtime-shield)
