<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Score;

/**
 * Security scoring categories — each maps to one or more rule IDs and
 * carries a configurable weight in the overall weighted score.
 */
enum ScoreCategory: string
{
    case AUTH = 'auth';
    case CSRF = 'csrf';
    case RATE_LIMIT = 'rate_limit';
    case VALIDATION = 'validation';
    case FILE_UPLOAD = 'file_upload';

    /** Human-readable display name for CLI tables and reports. */
    public function label(): string
    {
        return match ($this) {
            self::AUTH => 'Authentication',
            self::CSRF => 'CSRF Protection',
            self::RATE_LIMIT => 'Rate Limiting',
            self::VALIDATION => 'Input Validation',
            self::FILE_UPLOAD => 'File Upload Safety',
        };
    }

    /**
     * Default percentage weight this category contributes to the overall score.
     * The five defaults sum to 100.
     */
    public function defaultWeight(): int
    {
        return match ($this) {
            self::AUTH => 30,
            self::CSRF => 25,
            self::RATE_LIMIT => 20,
            self::VALIDATION => 15,
            self::FILE_UPLOAD => 10,
        };
    }

    /** One-line description of what the category evaluates. */
    public function description(): string
    {
        return match ($this) {
            self::AUTH => 'Routes protected by authentication middleware',
            self::CSRF => 'Mutable web routes covered by CSRF middleware',
            self::RATE_LIMIT => 'Routes with rate-limiting / throttle middleware',
            self::VALIDATION => 'Mutable routes with input validation middleware',
            self::FILE_UPLOAD => 'Upload endpoints with file-validation middleware',
        };
    }
}
