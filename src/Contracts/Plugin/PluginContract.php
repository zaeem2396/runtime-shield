<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Plugin;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Contracts\Signal\CustomSignalCollectorContract;

/**
 * Contract for a RuntimeShield plugin.
 *
 * A plugin is a self-contained extension that bundles one or more rules,
 * custom signal collectors, and any bootstrap logic into a single,
 * distributable unit.
 *
 * Plugins are registered via config('runtime_shield.extensibility.plugins')
 * and are automatically booted by the service provider at application start.
 *
 * Usage (extend AbstractPlugin for zero-boilerplate defaults):
 *
 *   final class MyPlugin extends AbstractPlugin
 *   {
 *       public function id(): string   { return 'my-plugin'; }
 *       public function name(): string { return 'My Plugin'; }
 *
 *       public function rules(): array
 *       {
 *           return [new MyCustomRule(), new AnotherRule()];
 *       }
 *   }
 */
interface PluginContract
{
    /** Unique machine-readable identifier, e.g. "my-company/my-plugin". */
    public function id(): string;

    /** Human-readable display name shown in CLI output. */
    public function name(): string;

    /**
     * Rules provided by this plugin.
     * They are automatically registered into the shared RuleRegistry on boot.
     *
     * @return list<RuleContract>
     */
    public function rules(): array;

    /**
     * Custom signal collectors provided by this plugin.
     * They are automatically registered into CustomSignalRegistry on boot.
     *
     * @return list<CustomSignalCollectorContract>
     */
    public function signalCollectors(): array;

    /**
     * Additional bootstrap logic for this plugin.
     * Called once per application lifecycle after rules and collectors have
     * been registered. Use this for binding services, event listeners, etc.
     */
    public function boot(): void;
}
