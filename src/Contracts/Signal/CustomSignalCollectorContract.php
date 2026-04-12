<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use Illuminate\Http\Request;

/**
 * Contract for a custom signal collector that enriches the request context
 * with application-specific data.
 *
 * Collectors are invoked during Phase 1 of the signal pipeline (handle phase)
 * immediately after the built-in request, route, and auth signals have been
 * captured. Their output is stored in CustomSignalStore keyed by the
 * collector's ID and can be read by custom rules or listeners.
 *
 * Usage:
 *
 *   final class TenantSignalCollector implements CustomSignalCollectorContract
 *   {
 *       public function id(): string { return 'tenant'; }
 *
 *       public function collect(Request $request): array
 *       {
 *           return ['tenant_id' => $request->header('X-Tenant-ID', 'default')];
 *       }
 *   }
 */
interface CustomSignalCollectorContract
{
    /** Unique machine-readable identifier for this collector. */
    public function id(): string;

    /**
     * Collect custom signals from the current request.
     *
     * Return an associative array of key → value pairs. The array will be
     * stored under this collector's ID in CustomSignalStore.
     *
     * @return array<string, mixed>
     */
    public function collect(Request $request): array;
}
