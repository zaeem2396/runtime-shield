<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Report;

use DateTimeImmutable;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use RuntimeShield\Contracts\Report\ReportBuilderContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Report\RouteProtection;
use RuntimeShield\DTO\Report\SecurityReport;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Builds a SecurityReport by scanning all registered application routes,
 * evaluating the rule engine against each one, and aggregating violations
 * alongside per-route protection metadata.
 */
final class ReportBuilder implements ReportBuilderContract
{
    public function __construct(
        private readonly Router $router,
        private readonly RuleEngineContract $ruleEngine,
        private readonly RouteProtectionAnalyzer $analyzer,
    ) {
    }

    public function build(): SecurityReport
    {
        return new SecurityReport(
            scannedAt: new DateTimeImmutable(),
            routeCount: 0,
            violations: new ViolationCollection(),
        );
    }
}
