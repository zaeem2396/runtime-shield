<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Advisory;

/**
 * Where advisory enrichment was requested from (CLI vs HTTP lifecycle).
 */
enum AdvisorySource: string
{
    case Cli = 'cli';
    case Http = 'http';
}
