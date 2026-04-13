<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core\Advisory;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Http\HttpTransportContract;
use RuntimeShield\Core\Advisory\OpenAiViolationAdvisoryEnricher;
use RuntimeShield\DTO\Advisory\AdvisorySource;
use RuntimeShield\DTO\Http\HttpResponse;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class OpenAiViolationAdvisoryEnricherTest extends TestCase
{
    #[Test]
    public function it_skips_when_http_source_and_enrich_http_disabled(): void
    {
        $transport = new class () implements HttpTransportContract {
            public int $calls = 0;

            public function post(string $url, array $headers, string $body, int $timeoutMs): HttpResponse
            {
                ++$this->calls;

                return new HttpResponse(200, '{}');
            }
        };

        $enricher = new OpenAiViolationAdvisoryEnricher(
            ['enabled' => true, 'api_key' => 'k', 'enrich_http_requests' => false],
            $transport,
        );

        $v = new Violation('r', 'T', 'D', Severity::HIGH);
        $out = $enricher->enrich(new ViolationCollection([$v]), AdvisorySource::Http);

        $this->assertSame(0, $transport->calls);
        $this->assertNull($out->all()[0]->advisory);
    }

    #[Test]
    public function it_attaches_advisory_from_openai_response(): void
    {
        $inner = (string) json_encode([
            'advisories' => [
                [
                    'summary' => 'Public route risk',
                    'impact' => 'Data exposure',
                    'remediation' => 'Add auth',
                    'advisory_severity' => 'high',
                    'confidence' => 0.85,
                    'rationale' => 'No middleware',
                ],
            ],
        ]);

        $openaiBody = (string) json_encode([
            'choices' => [
                ['message' => ['content' => $inner]],
            ],
        ]);

        $transport = new class ($openaiBody) implements HttpTransportContract {
            public function __construct(private readonly string $payload)
            {
            }

            public function post(string $url, array $headers, string $body, int $timeoutMs): HttpResponse
            {
                return new HttpResponse(200, $this->payload);
            }
        };

        $enricher = new OpenAiViolationAdvisoryEnricher(
            [
                'enabled' => true,
                'api_key' => 'sk-test',
                'base_url' => 'https://api.openai.com/v1',
                'batch_size' => 10,
            ],
            $transport,
        );

        $v = new Violation('public-route-without-auth', 'Public Route', 'Desc', Severity::MEDIUM, '/x');
        $out = $enricher->enrich(new ViolationCollection([$v]), AdvisorySource::Cli);

        $adv = $out->all()[0]->advisory;
        $this->assertNotNull($adv);
        $this->assertSame('Public route risk', $adv->summary);
        $this->assertSame(Severity::HIGH, $adv->advisorySeverity);
        $this->assertSame(0.85, $adv->confidence);
    }

    #[Test]
    public function it_returns_original_on_http_error(): void
    {
        $transport = new class () implements HttpTransportContract {
            public function post(string $url, array $headers, string $body, int $timeoutMs): HttpResponse
            {
                return new HttpResponse(500, 'err');
            }
        };

        $enricher = new OpenAiViolationAdvisoryEnricher(
            ['enabled' => true, 'api_key' => 'k'],
            $transport,
        );

        $v = new Violation('r', 'T', 'D', Severity::LOW);
        $out = $enricher->enrich(new ViolationCollection([$v]), AdvisorySource::Cli);

        $this->assertNull($out->all()[0]->advisory);
    }
}
