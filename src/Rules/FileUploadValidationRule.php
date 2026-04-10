<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fires when a POST route whose URI suggests file-upload behaviour has no
 * known file-size or upload-validation middleware.
 *
 * Unvalidated file uploads can lead to server-side code execution, storage
 * exhaustion, or MIME-type confusion attacks.
 */
final class FileUploadValidationRule implements RuleContract
{
    /**
     * URI path fragments that suggest a file-upload endpoint.
     *
     * @var list<string>
     */
    private const UPLOAD_KEYWORDS = [
        'upload',
        'file',
        'image',
        'photo',
        'avatar',
        'attachment',
        'media',
        'document',
        'import',
    ];

    /**
     * Middleware that indicates upload validation is in place.
     *
     * @var list<string>
     */
    private const UPLOAD_MIDDLEWARE = [
        'validate-post-size',
        'file-validation',
        'upload-validation',
        'max-file-size',
    ];

    public function id(): string
    {
        return 'file-upload-without-validation';
    }

    public function title(): string
    {
        return 'File Upload Without Validation';
    }

    public function severity(): Severity
    {
        return Severity::MEDIUM;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $route = $context->route;
        $request = $context->request;

        if ($route === null || $request === null) {
            return [];
        }

        if (strtoupper($request->method) !== 'POST') {
            return [];
        }

        $lowerUri = strtolower($route->uri);
        $isUpload = false;

        foreach (self::UPLOAD_KEYWORDS as $keyword) {
            if (str_contains($lowerUri, $keyword)) {
                $isUpload = true;

                break;
            }
        }

        if (! $isUpload) {
            return [];
        }

        foreach ($route->middleware as $middleware) {
            foreach (self::UPLOAD_MIDDLEWARE as $mw) {
                if (str_starts_with($middleware, $mw)) {
                    return [];
                }
            }
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Route '{$route->uri}' appears to accept file uploads but has no upload-validation middleware.",
                severity: $this->severity(),
                route: $route->uri,
                context: ['method' => $request->method, 'uri' => $route->uri, 'middleware' => $route->middleware],
            ),
        ];
    }
}
