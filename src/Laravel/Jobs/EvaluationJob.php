<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Queueable job that runs the RuleEngine against a SecurityRuntimeContext
 * off the HTTP request critical path.
 *
 * Dispatched by AsyncRuleEngine when runtime_shield.performance.async = true.
 * The SecurityRuntimeContext is serialized via the Laravel queue layer; all
 * signal DTOs are plain PHP objects with primitive properties, making them
 * safely serializable.
 */
final class EvaluationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly SecurityRuntimeContext $context)
    {
    }

    /**
     * Execute the rule evaluation on the queue worker.
     * Violations are discarded here; hook into the RuleEngine or use
     * an observer if you need to act on async violations.
     */
    public function handle(RuleEngineContract $ruleEngine): void
    {
        $ruleEngine->run($this->context);
    }
}
