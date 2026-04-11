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
    */
    'sampling_rate' => (float) env('RUNTIME_SHIELD_SAMPLING_RATE', 1.0),

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

];
