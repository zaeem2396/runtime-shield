<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Plugin;

use RuntimeShield\Contracts\Plugin\PluginContract;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Core\Signal\CustomSignalRegistry;

/**
 * Mutable registry of RuntimeShield plugins.
 *
 * Bound as a singleton in the service container. Plugins are registered
 * at boot time and their rules and signal collectors are propagated to
 * the respective registries via boot().
 */
final class PluginRegistry
{
    /** @var list<PluginContract> */
    private array $plugins = [];

    public function register(PluginContract $plugin): void
    {
        $this->plugins[] = $plugin;
    }

    /**
     * Return all registered plugins.
     *
     * @return list<PluginContract>
     */
    public function all(): array
    {
        return $this->plugins;
    }

    public function count(): int
    {
        return count($this->plugins);
    }

    public function has(string $id): bool
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->id() === $id) {
                return true;
            }
        }

        return false;
    }

    public function find(string $id): PluginContract|null
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->id() === $id) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Boot all registered plugins:
     *  1. Register each plugin's rules into the shared RuleRegistry.
     *  2. Register each plugin's signal collectors into CustomSignalRegistry.
     *  3. Call each plugin's boot() for any additional setup.
     */
    public function boot(RuleRegistry $rules, CustomSignalRegistry $signals): void
    {
        foreach ($this->plugins as $plugin) {
            foreach ($plugin->rules() as $rule) {
                $rules->register($rule);
            }

            foreach ($plugin->signalCollectors() as $collector) {
                $signals->register($collector);
            }

            $plugin->boot();
        }
    }
}
