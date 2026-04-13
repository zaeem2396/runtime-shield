<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable RuntimeShield
    |--------------------------------------------------------------------------
    |
    | Master switch for the entire package. Setting this to false results in
    | absolute zero overhead — no middleware processing, no rule evaluation,
    | no allocations. Safe to disable per-environment via an env var.
    |
    */
    'enabled' => (bool) env('RUNTIME_SHIELD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Sampling Rate
    |--------------------------------------------------------------------------
    |
    | A float between 0.0 and 1.0 controlling what fraction of requests are
    | processed. 1.0 = every request (default), 0.5 = 50%, 0.0 = disabled.
    | Useful for high-traffic production environments to reduce overhead.
    |
    | Per-environment overrides can be set under sampling.env_rates below.
    | When env_rates is populated the global sampling_rate acts as a fallback
    | for environments not listed.
    |
    */
    'sampling_rate' => (float) env('RUNTIME_SHIELD_SAMPLING_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Dynamic Sampling
    |--------------------------------------------------------------------------
    |
    | Override the sampling rate per application environment (APP_ENV).
    | Set env_rates to an empty array [] to disable per-env overrides and
    | fall back to the global sampling_rate above.
    |
    | Example:
    |   'env_rates' => [
    |       'production' => 0.5,   // sample half of production traffic
    |       'staging'    => 0.8,
    |       'testing'    => 0.0,   // never sample in tests (zero overhead)
    |       'local'      => 1.0,   // always sample locally
    |   ],
    |
    */
    'sampling' => [
        'env_rates' => [
            // 'production' => 0.5,
            // 'staging'    => 0.8,
            // 'testing'    => 0.0,
            // 'local'      => 1.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Groups
    |--------------------------------------------------------------------------
    |
    | Toggle individual rule categories. Disabled groups are skipped entirely
    | at evaluation time — their rules are never instantiated.
    |
    */
    'rules' => [
        'auth'       => true,
        'rate_limit' => true,
        'csrf'       => true,
        'validation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Score Weights
    |--------------------------------------------------------------------------
    |
    | Each category's percentage contribution to the overall security score.
    | Values must be integers; they are normalised so they don't have to sum
    | to exactly 100, but keeping them at 100 makes reasoning easier.
    |
    | Thresholds determine the CLI display colour and pass/fail status:
    |   pass    — score >= this value is shown in green (B or above)
    |   warning — score >= this value is shown in yellow (between pass & warning)
    |   fail    — score below warning is shown in red
    |
    */
    'scoring' => [
        'weights' => [
            'auth'        => 30,
            'csrf'        => 25,
            'rate_limit'  => 20,
            'validation'  => 15,
            'file_upload' => 10,
        ],
        'thresholds' => [
            'pass'    => 75,
            'warning' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Tune runtime characteristics of the evaluation engine.
    | async: queue rule evaluation off the critical request path (v0.7.0+)
    | batch_size: how many rules to evaluate per batch
    | timeout_ms: abort evaluation after this many milliseconds
    |
    */
    'performance' => [
        'async'      => false,
        'batch_size' => 50,
        'timeout_ms' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting & Notifications (v0.8.0+)
    |--------------------------------------------------------------------------
    |
    | Controls when and how RuntimeShield sends alerts when violations are
    | detected during a real HTTP request (i.e. via the middleware, not CLI).
    |
    | enabled        — master switch; false = no alerts, no rule evaluation
    | min_severity   — only violations at this severity or higher trigger alerts
    |                  values: critical | high | medium | low | info
    | throttle_seconds — cooldown per rule; 0 = no throttling
    | async          — dispatch AlertDispatchJob to the queue instead of
    |                  blocking the terminate() lifecycle
    |
    | Channels:
    |   log.enabled       — write a structured log entry (always safe to enable)
    |   log.channel       — Laravel log channel name (e.g. "stack", "slack")
    |   webhook.enabled   — POST JSON payload to webhook.url
    |   webhook.url       — target URL (leave empty to disable)
    |   webhook.method    — HTTP method (default POST)
    |   webhook.headers   — extra HTTP headers for every webhook request
    |   slack.enabled     — send a formatted message to a Slack Incoming Webhook
    |   slack.url         — Slack webhook URL (leave empty to disable)
    |   mail.enabled      — send a plain-text email
    |   mail.recipients   — list of recipient addresses
    |   mail.from         — sender address (falls back to MAIL_FROM_ADDRESS)
    |
    */
    'alerts' => [
        'enabled'          => (bool) env('RUNTIME_SHIELD_ALERTS_ENABLED', false),
        'min_severity'     => env('RUNTIME_SHIELD_ALERT_MIN_SEVERITY', 'high'),
        'throttle_seconds' => (int) env('RUNTIME_SHIELD_ALERT_THROTTLE', 300),
        'async'            => (bool) env('RUNTIME_SHIELD_ALERTS_ASYNC', false),

        'channels' => [
            'log' => [
                'enabled' => (bool) env('RUNTIME_SHIELD_ALERT_LOG', true),
                'channel' => env('RUNTIME_SHIELD_LOG_CHANNEL', 'stack'),
            ],

            'webhook' => [
                'enabled' => (bool) env('RUNTIME_SHIELD_ALERT_WEBHOOK', false),
                'url'     => env('RUNTIME_SHIELD_WEBHOOK_URL', ''),
                'method'  => env('RUNTIME_SHIELD_WEBHOOK_METHOD', 'POST'),
                'headers' => [],
            ],

            'slack' => [
                'enabled' => (bool) env('RUNTIME_SHIELD_ALERT_SLACK', false),
                'url'     => env('RUNTIME_SHIELD_SLACK_WEBHOOK_URL', ''),
            ],

            'mail' => [
                'enabled'    => (bool) env('RUNTIME_SHIELD_ALERT_MAIL', false),
                'recipients' => [],
                'from'       => env('MAIL_FROM_ADDRESS', ''),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensibility
    |--------------------------------------------------------------------------
    |
    | Register custom rules, signal collectors, and plugins that extend
    | RuntimeShield's core behaviour without modifying package source code.
    |
    | rules            — FQCN list of classes implementing RuleContract (or
    |                    extending AbstractRule). Each is resolved from the
    |                    container so constructor injection is supported.
    |
    | signal_collectors — FQCN list of classes implementing
    |                    CustomSignalCollectorContract. Each collector is
    |                    invoked during Phase 1 of the signal pipeline and its
    |                    key→value output is stored in CustomSignalStore.
    |
    | plugins          — FQCN list of classes implementing PluginContract (or
    |                    extending AbstractPlugin). Plugins bundle rules and
    |                    collectors into reusable, distributable packages.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    |
    | When enabled, RuntimeShield fires Laravel events at key scan lifecycle
    | points: BeforeScanEvent, AfterScanEvent, and ViolationDetectedEvent.
    |
    | Disable this when you do not use the events to avoid the Dispatcher
    | dispatch overhead on every evaluated request.
    |
    */
    'events' => [
        'enabled' => (bool) env('RUNTIME_SHIELD_EVENTS_ENABLED', true),
    ],

    'extensibility' => [

        'rules' => [
            // App\Rules\MyCustomRule::class,
        ],

        'signal_collectors' => [
            // App\Signals\MySignalCollector::class,
        ],

        'plugins' => [
            // App\Plugins\MyPlugin::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | AI Advisory (v1.0.0+)
    |--------------------------------------------------------------------------
    |
    | Optional OpenAI-compatible advisory metadata on violations (summary,
    | impact, remediation, advisory severity hint, confidence). Deterministic
    | rule severity is never modified. When disabled or on API failure, scans
    | behave exactly as before.
    |
    | enrich_http_requests — when true, middleware/alert evaluation may call
    | the AI API (use with care in production). CLI scan/report honor
    | ai.enabled unless you pass --no-ai.
    |
    */
    'ai' => [
        'enabled' => (bool) env('RUNTIME_SHIELD_AI_ENABLED', false),
        'enrich_http_requests' => (bool) env('RUNTIME_SHIELD_AI_ENRICH_HTTP', false),
        'api_key' => (string) env('RUNTIME_SHIELD_AI_API_KEY', ''),
        'base_url' => (string) env('RUNTIME_SHIELD_AI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => (string) env('RUNTIME_SHIELD_AI_MODEL', 'gpt-4o-mini'),
        'timeout_ms' => (int) env('RUNTIME_SHIELD_AI_TIMEOUT_MS', 60_000),
        'max_tokens' => (int) env('RUNTIME_SHIELD_AI_MAX_TOKENS', 4096),
        'batch_size' => (int) env('RUNTIME_SHIELD_AI_BATCH_SIZE', 20),
    ],

];
