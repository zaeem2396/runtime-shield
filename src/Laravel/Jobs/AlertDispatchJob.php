<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Queueable job that dispatches alert notifications off the HTTP critical path.
 *
 * When runtime_shield.alerts.async = true the middleware hands off the
 * ViolationCollection to this job instead of calling AlertDispatcherContract
 * synchronously, ensuring HTTP response latency is not affected by slow
 * alert channels (webhook timeouts, mail delivery, etc.).
 *
 * ViolationCollection and its Violation records are plain PHP objects with
 * primitive properties and are therefore safely serializable by the queue.
 */
final class AlertDispatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ViolationCollection $violations,
        public readonly string $route,
    ) {
    }

    /**
     * Execute alert delivery on the queue worker.
     */
    public function handle(AlertDispatcherContract $dispatcher): void
    {
        $dispatcher->dispatch($this->violations, $this->route);
    }
}
