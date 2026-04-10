<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\RuntimeContextBuilder;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\DTO\Signal\RequestSignal;
use RuntimeShield\DTO\Signal\RouteSignal;
use RuntimeShield\Rules\FileUploadValidationRule;

final class FileUploadValidationRuleTest extends TestCase
{
    private FileUploadValidationRule $rule;

    #[Test]
    public function it_has_correct_id_and_severity(): void
    {
        $this->assertSame('file-upload-without-validation', $this->rule->id());
        $this->assertSame(Severity::MEDIUM, $this->rule->severity());
    }

    #[Test]
    public function it_fires_for_post_route_with_upload_keyword(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', 'api/upload'));

        $this->assertCount(1, $violations);
        $this->assertSame(Severity::MEDIUM, $violations[0]->severity);
    }

    #[Test]
    public function it_fires_for_post_route_with_file_keyword(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', 'documents/file'));

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function it_fires_for_post_route_with_image_keyword(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', 'users/avatar/image'));

        $this->assertCount(1, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_get_request_even_with_upload_keyword(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('GET', 'upload/list'));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_does_not_fire_for_post_without_upload_keywords(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', 'users/create'));

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function it_returns_empty_when_route_or_request_signal_is_null(): void
    {
        $context = (new RuntimeContextBuilder())->build();

        $this->assertCount(0, $this->rule->evaluate($context));
    }

    #[Test]
    public function violation_references_the_upload_uri(): void
    {
        $violations = $this->rule->evaluate($this->makeContext('POST', 'media/upload'));

        $this->assertStringContainsString('media/upload', $violations[0]->route);
    }

    protected function setUp(): void
    {
        $this->rule = new FileUploadValidationRule();
    }

    private function makeContext(string $method, string $uri, array $middleware = []): SecurityRuntimeContext
    {
        $route = new RouteSignal('', $uri, 'Closure', '', $middleware, false);
        $request = new RequestSignal($method, "http://localhost/{$uri}", "/{$uri}", '127.0.0.1', [], [], 0, new \DateTimeImmutable());

        return (new RuntimeContextBuilder())->withRoute($route)->withRequest($request)->build();
    }
}
