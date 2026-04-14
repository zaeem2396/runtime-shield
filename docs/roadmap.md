# 🚀 RuntimeShield — Full Build Execution Guide (v0.1.0 → v1.2.0)

This document contains **Cursor-ready prompts mapped to release versions**.

> ⚡ Execute in order
> ⚡ Tag versions after each section
> ⚡ Don't skip — architecture depends on it

---

## Legend

| Label | Meaning |
|-------|---------|
| 🟢 **Completed** | Shipped, tested, committed |
| 🟡 **In Progress** | Active development branch open |
| 🔴 **Planned** | Not yet started |

---

# 🧱 v0.1.0 — Foundation &nbsp;🟢 Completed

## STEP 1 — Package Skeleton &nbsp;🟢 Completed

```
You are building a production-grade PHP package called RuntimeShield.

Requirements:
- PHP 8.2+
- Framework agnostic core
- Laravel support first
- SOLID architecture

Create structure:

src/
  Core/
  Contracts/
  DTO/
  Engine/
  Rules/
  Support/

src/Laravel/
  Providers/
  Middleware/
  Console/

config/runtime_shield.php

composer.json (PSR-4: RuntimeShield\\ => src/)

Rules:
- Core must be framework-agnostic
- Laravel code only in src/Laravel
- No logic yet, only structure
```

---

## STEP 2 — Service Provider &nbsp;🟢 Completed

```
Create RuntimeShieldServiceProvider.

Register:
- config publishing
- singleton bindings
- CLI commands

Ensure:
- Works with vendor:publish
```

---

## STEP 3 — Config System &nbsp;🟢 Completed

```
Create runtime_shield.php config:

enabled, sampling_rate, rules, performance.

Create ConfigRepository abstraction.
```

---

## STEP 4 — Enable/Disable System &nbsp;🟢 Completed

```
Implement RuntimeShieldManager::isEnabled()

Ensure:
- zero overhead when disabled
```

---

✅ `git tag v0.1.0`

---

# 🔍 v0.2.0 — Runtime Observation &nbsp;🟢 Completed

## STEP 5 — Request Middleware &nbsp;🟢 Completed

```
Capture request:
method, URL, headers, query, IP, size

Convert to RequestSignal DTO
```

---

## STEP 6 — Response Listener &nbsp;🟢 Completed

```
Capture:
status, headers, size, response time
```

---

## STEP 7 — Route + Auth Signals &nbsp;🟢 Completed

```
Create RouteSignal + AuthSignal

Extract from Laravel router + auth
```

---

## STEP 8 — DTO Layer &nbsp;🟢 Completed

```
Create immutable DTOs:
Request, Response, Route, Auth
```

---

✅ `git tag v0.2.0`

---

# 🧠 v0.3.0 — Signal Engine &nbsp;🟢 Completed

## STEP 9 — Runtime Context &nbsp;🟢 Completed

```
Create SecurityRuntimeContext

Use builder pattern
Immutable object
```

---

## STEP 10 — Normalization Layer &nbsp;🟢 Completed

```
Convert Laravel data → unified DTOs
```

---

## STEP 11 — In-Memory Store &nbsp;🟢 Completed

```
Store signals per request lifecycle
```

---

## STEP 12 — Sampling &nbsp;🟢 Completed

```
Add percentage-based sampling
```

---

✅ `git tag v0.3.0`

---

# ⚙️ v0.4.0 — Rule Engine (MVP Core) &nbsp;🟢 Completed

## STEP 13 — Rule Engine &nbsp;🟢 Completed

```
Create:
RuleInterface
RuleEngine
Violation DTO
```

---

## STEP 14 — Core Rules &nbsp;🟢 Completed

```
Implement:
- PublicRouteWithoutAuthRule
- MissingRateLimitRule
- MissingCsrfRule
```

---

## STEP 15 — Validation Rules &nbsp;🟢 Completed

```
Implement:
- MissingValidationRule
- FileUploadValidationRule
```

---

## STEP 16 — CLI Scanner &nbsp;🟢 Completed

```
Command:
runtime:security:scan

Output:
Route | Issues | Severity

Add colors + grouping
```

---

✅ `git tag v0.4.0`

---

# 🧪 v0.5.0 — CLI Experience (Viral Layer) &nbsp;🟢 Completed

## STEP 17 — Report Command &nbsp;🟢 Completed

```
Create:
runtime:security:report

Show summarized report
```

---

## STEP 18 — Pretty CLI Output &nbsp;🟢 Completed

```
Add:
- tables
- colored output
- sections
- emojis/icons
```

---

## STEP 19 — Issue Grouping &nbsp;🟢 Completed

```
Group by:
- Critical
- Warning
- Info
```

---

## STEP 20 — Route Inspection Command &nbsp;🟢 Completed

```
Command:
runtime:security:routes

List routes + protections
```

---

✅ `git tag v0.5.0`

---

# 📊 v0.6.0 — Security Score &nbsp;🟢 Completed

## STEP 21 — Score Engine &nbsp;🟢 Completed

```
Calculate score based on violations
```

---

## STEP 22 — Category Scoring &nbsp;🟢 Completed

```
Break score into:
auth, validation, csrf, etc.
```

---

## STEP 23 — CLI Score Output &nbsp;🟢 Completed

```
Display:
Security Score: 78/100
```

---

## STEP 24 — Score Breakdown &nbsp;🟢 Completed

```
Show category-wise breakdown
```

---

✅ `git tag v0.6.0`

---

# ⚡ v0.7.0 — Performance &nbsp;🟢 Completed

## STEP 25 — Middleware Optimization &nbsp;🟢 Completed

```
Reduce overhead
Avoid unnecessary allocations
```

---

## STEP 26 — Async Processing &nbsp;🟢 Completed

```
Queue-based rule evaluation
```

---

## STEP 27 — Batch Rule Execution &nbsp;🟢 Completed

```
Execute rules in batches
```

---

## STEP 28 — Dynamic Sampling &nbsp;🟢 Completed

```
Configurable per env
```

---

✅ `git tag v0.7.0`

---

# 🔔 v0.8.0 — Alerting &amp; Notifications &nbsp;🟢 Completed

## STEP 29 — Webhook Dispatcher &nbsp;🟢 Completed

```
Dispatch HTTP webhook on critical violations
Configurable URL, method, and payload
```

---

## STEP 30 — Alert Throttling &nbsp;🟢 Completed

```
Prevent alert floods
Cooldown period per rule / channel
```

---

## STEP 31 — Mail Notifications &nbsp;🟢 Completed

```
Send email on CRITICAL or HIGH violations
Configurable recipients and severity threshold
```

---

## STEP 32 — Alert Channels &nbsp;🟢 Completed

```
Configurable alert channels:
log, mail, webhook, Slack
```

---

✅ `git tag v0.8.0`

---

# 🔌 v0.9.0 — Extensibility &nbsp;🟢 Completed

## STEP 33 — Custom Rule API &nbsp;🟢 Completed

```
Allow user-defined rules
```

---

## STEP 34 — Custom Signal Collectors &nbsp;🟢 Completed

```
Allow adding custom signals
```

---

## STEP 35 — Plugin System &nbsp;🟢 Completed

```
Register external plugins
```

---

## STEP 36 — Event Hooks &nbsp;🟢 Completed

```
Emit events:
beforeScan, afterScan, violationDetected
```

---

✅ `git tag v0.9.0`

---

# 🤖 v1.0.0 — AI Advisory &nbsp;🟢 Completed

Goal: add optional AI-generated advisory context without compromising deterministic scanning.

Rollout: ship behind `ai.enabled=false` default, then enable per-environment gradually.

Shipped: OpenAI-compatible Chat Completions advisory on violations (`ViolationAdvisory`), CLI
`runtime-shield:scan` / `runtime-shield:report` with `--no-ai`, optional HTTP enrichment behind
`ai.enrich_http_requests`, config under `runtime_shield.ai`. Deterministic rule severity and
scoring unchanged.

## STEP 37 — AI Explanation Layer &nbsp;🟢 Completed

```
Explain violations in human-readable format
Define strict advisory DTO schema for stable output
```

Acceptance criteria:
- Explanation includes summary, impact, and remediation.
- Missing provider credentials never break scan command output.

---

## STEP 38 — Severity Classification &nbsp;🟢 Completed

```
AI-assisted severity scoring
Keep deterministic severity as baseline and store advisory severity separately
```

Acceptance criteria:
- Advisory severity never overwrites deterministic rule severity.
- `runtime-shield:score` remains based on deterministic severity only.

---

## STEP 39 — Confidence Score &nbsp;🟢 Completed

```
Assign confidence level per issue
Document score range normalization to 0.00-1.00
```

Acceptance criteria:
- Confidence values are bounded and validated.
- Confidence defaults to `null` when advisory is unavailable.

---

## STEP 40 — AI Config &nbsp;🟢 Completed

```
Enable/disable AI
Support multiple providers
Add timeout and retry guardrails
```

Acceptance criteria:
- Provider, timeout, and token limits are configurable via env vars.
- Disabling AI skips provider calls entirely.

---

✅ `git tag v1.0.0`

Release gate checklist:
- [x] `composer run format:test`
- [x] `composer run analyse`
- [x] `composer run test`
- [x] Document provider-specific setup examples in README
- [x] Add upgrade note in CHANGELOG

---

# 📈 v1.1.0 — Advanced Detection &nbsp;🟡 In Progress

## STEP 41 — Error Exposure Detection &nbsp;🟢 Completed

```
Detect stack trace leaks
```

Implementation: added `ErrorExposureRule` to flag 5xx responses that expose debug/exception indicators
(`x-debug-*` headers, suspicious HTML 5xx payloads).

---

## STEP 42 — Brute Force Detection &nbsp;🟢 Completed

```
Detect repeated 401 patterns
```

Implementation: added `BruteForcePatternRule` to detect 401 failures on auth-like endpoints when
no throttle/rate-limit middleware is present.

---

## STEP 43 — Security Headers &nbsp;🟢 Completed

```
Check:
CSP, HSTS, X-Frame-Options
```

Implementation: added `MissingSecurityHeadersRule` for CSP and X-Frame-Options, plus HSTS checks
on HTTPS requests.

---

## STEP 44 — Response Anomalies &nbsp;🟢 Completed

```
Detect abnormal responses
```

Implementation: added `ResponseAnomalyRule` for very slow responses, oversized bodies, empty 5xx
responses, and malformed 204 bodies.

---

Release status:
- [x] Runtime rules implemented and registered
- [x] Rule-level unit tests added
- [x] README + CHANGELOG updated
- [ ] `git tag v1.1.0`

---

# 🖥️ v1.2.0 — Developer Experience &nbsp;🔴 Planned

## STEP 45 — Debug Dashboard &nbsp;🔴 Planned

```
Optional UI:
visualize signals + issues
```

---

## STEP 46 — JSON Export &nbsp;🔴 Planned

```
Export reports in JSON
```

---

## STEP 47 — CI Integration &nbsp;🔴 Planned

```
Fail CI if score < threshold
```

---

✅ `git tag v1.2.0`

---

# 🎯 FINAL STATE

At v1.2.0 you will have:

* 🛡️ Runtime security detection engine
* 🧠 Intelligent rule system
* 🤖 AI-powered explanations
* 📊 Security scoring
* ⚡ Performance optimized
* 🔌 Extensible architecture
* 🖥️ Developer-friendly tools
