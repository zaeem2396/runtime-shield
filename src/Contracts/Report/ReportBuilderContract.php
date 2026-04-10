<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Report;

use RuntimeShield\DTO\Report\SecurityReport;

/**
 * Contract for building a SecurityReport by scanning all registered routes.
 */
interface ReportBuilderContract
{
    /**
     * Scan every registered route, evaluate all rules, and return the
     * aggregated SecurityReport.
     */
    public function build(): SecurityReport;
}
