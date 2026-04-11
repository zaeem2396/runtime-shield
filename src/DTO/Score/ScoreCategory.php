<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Score;

/**
 * Security scoring categories — each maps to one or more rule IDs and
 * carries a configurable weight in the overall weighted score.
 */
enum ScoreCategory: string
{
    case AUTH        = 'auth';
    case CSRF        = 'csrf';
    case RATE_LIMIT  = 'rate_limit';
    case VALIDATION  = 'validation';
    case FILE_UPLOAD = 'file_upload';
}
