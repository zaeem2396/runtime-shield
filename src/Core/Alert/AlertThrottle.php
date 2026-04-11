<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

/**
 * In-memory cooldown tracker that prevents the same rule from triggering
 * repeated alerts within a configurable time window.
 *
 * Records are keyed by rule ID and store the Unix timestamp of the most
 * recent alert. A rule is considered throttled when the elapsed time
 * since the last alert is less than $cooldownSeconds.
 *
 * This implementation is in-memory and therefore process-scoped — records
 * reset between PHP-FPM workers. For cross-process throttling use a
 * persistent driver (Redis / database) and swap this class out.
 */
final class AlertThrottle
{
    /** @var array<string, int> ruleId → unix timestamp of last alert */
    private array $records = [];

    public function __construct(private readonly int $cooldownSeconds = 300)
    {
    }

    /**
     * Whether the given rule is still within its cooldown window and
     * should NOT trigger another alert.
     */
    public function isThrottled(string $ruleId): bool
    {
        if (! array_key_exists($ruleId, $this->records)) {
            return false;
        }

        return (time() - $this->records[$ruleId]) < $this->cooldownSeconds;
    }

    /**
     * Record that the given rule just triggered an alert, starting its
     * cooldown window from the current time.
     */
    public function record(string $ruleId): void
    {
        $this->records[$ruleId] = time();
    }

    /** The configured cooldown window in seconds. */
    public function cooldownSeconds(): int
    {
        return $this->cooldownSeconds;
    }

    /** Number of rules currently in the throttle table. */
    public function count(): int
    {
        return count($this->records);
    }

    /** Clear all throttle records, allowing every rule to alert immediately. */
    public function flush(): void
    {
        $this->records = [];
    }
}
