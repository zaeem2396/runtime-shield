<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Plugin;

use RuntimeShield\Contracts\Plugin\PluginContract;

/**
 * Convenience base class for plugin implementations.
 *
 * Provides empty defaults for rules(), signalCollectors(), and boot() so that
 * concrete plugins only need to override what they actually need.
 *
 * Subclasses MUST implement id() and name().
 *
 * Example:
 *
 *   final class SqlInjectionPlugin extends AbstractPlugin
 *   {
 *       public function id(): string   { return 'acme/sql-injection'; }
 *       public function name(): string { return 'Acme — SQL Injection Detector'; }
 *
 *       public function rules(): array
 *       {
 *           return [new RawQueryRule(), new UnboundParameterRule()];
 *       }
 *   }
 */
abstract class AbstractPlugin implements PluginContract
{
    /**
     * @return list<\RuntimeShield\Contracts\Rule\RuleContract>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return list<\RuntimeShield\Contracts\Signal\CustomSignalCollectorContract>
     */
    public function signalCollectors(): array
    {
        return [];
    }

    public function boot(): void
    {
        // No-op by default; override when bootstrap logic is needed.
    }
}
