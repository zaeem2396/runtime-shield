<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Advisory;

use Psr\Log\LoggerInterface;
use RuntimeShield\Contracts\Advisory\ViolationAdvisoryEnricherContract;
use RuntimeShield\Contracts\Http\HttpTransportContract;
use RuntimeShield\DTO\Advisory\AdvisorySource;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationAdvisory;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Calls an OpenAI-compatible Chat Completions API to attach {@see ViolationAdvisory}
 * records. On any failure, returns the original violations unchanged.
 */
final class OpenAiViolationAdvisoryEnricher implements ViolationAdvisoryEnricherContract
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are a security advisor for PHP Laravel applications. The user message is JSON with a key "violations" (array). Return a JSON object with exactly one key "advisories": an array of the SAME length as "violations", in the same order. Each element must be an object with keys: summary (string), impact (string), remediation (string), advisory_severity (one of: critical, high, medium, low, info, or null), confidence (number between 0 and 1, or null), rationale (string). advisory_severity is only a triage hint and must not replace the original rule severity. Be concise and actionable. Do not include markdown code fences.
        PROMPT;

    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
        private readonly HttpTransportContract $http,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function enrich(ViolationCollection $violations, AdvisorySource $source): ViolationCollection
    {
        if (! $this->shouldEnrich($source) || $violations->isEmpty()) {
            return $violations;
        }

        $apiKey = $this->stringConfig('api_key', '');

        if ($apiKey === '') {
            return $violations;
        }

        $batchSize = max(1, $this->intConfig('batch_size', 20));
        $all = $violations->all();
        /** @var list<list<Violation>> $chunks */
        $chunks = array_chunk($all, $batchSize);
        $merged = [];

        try {
            foreach ($chunks as $chunk) {
                $merged = array_merge($merged, $this->enrichChunk($chunk, $apiKey));
            }
        } catch (\Throwable $e) {
            $this->logger?->warning('RuntimeShield AI advisory enrichment failed: ' . $e->getMessage());

            return $violations;
        }

        return new ViolationCollection($merged);
    }

    private function shouldEnrich(AdvisorySource $source): bool
    {
        if (! (bool) ($this->config['enabled'] ?? false)) {
            return false;
        }

        if ($source === AdvisorySource::Http && ! (bool) ($this->config['enrich_http_requests'] ?? false)) {
            return false;
        }

        return true;
    }

    /**
     * @param list<Violation> $chunk
     *
     * @return list<Violation>
     */
    private function enrichChunk(array $chunk, string $apiKey): array
    {
        $baseUrl = rtrim($this->stringConfig('base_url', 'https://api.openai.com/v1'), '/');
        $model = $this->stringConfig('model', 'gpt-4o-mini');
        $timeoutMs = max(100, $this->intConfig('timeout_ms', 1200));
        $maxTokens = max(64, $this->intConfig('max_tokens', 800));

        $userContent = $this->buildUserPayload($chunk);

        $body = (string) json_encode([
            'model' => $model,
            'temperature' => 0.2,
            'max_tokens' => $maxTokens,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $userContent],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->http->post(
            $baseUrl . '/chat/completions',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            $body,
            $timeoutMs,
        );

        if (! $response->isSuccess()) {
            $this->logger?->notice('RuntimeShield AI HTTP non-success', [
                'status' => $response->statusCode,
            ]);

            return $chunk;
        }

        $advisories = $this->parseOpenAiResponse($response->body);

        return $this->applyAdvisories($chunk, $advisories);
    }

    /**
     * @param list<Violation> $chunk
     */
    private function buildUserPayload(array $chunk): string
    {
        $minimal = [];

        foreach ($chunk as $i => $v) {
            $minimal[] = [
                'index' => $i,
                'rule_id' => $v->ruleId,
                'title' => $v->title,
                'description' => $v->description,
                'severity' => $v->severity->value,
                'route' => $v->route,
            ];
        }

        return (string) json_encode(['violations' => $minimal], JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<mixed>|null
     */
    private function parseOpenAiResponse(string $body): ?array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $choices = $decoded['choices'] ?? null;

        if (! is_array($choices) || $choices === [] || ! is_array($choices[0] ?? null)) {
            return null;
        }

        /** @var array<string, mixed> $first */
        $first = $choices[0];
        $message = $first['message'] ?? null;

        if (! is_array($message)) {
            return null;
        }

        $content = $message['content'] ?? null;

        if (! is_string($content)) {
            return null;
        }

        try {
            /** @var array<string, mixed> $inner */
            $inner = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $advisories = $inner['advisories'] ?? null;

        if (! is_array($advisories)) {
            return null;
        }

        /** @var list<mixed> $asList */
        $asList = array_values($advisories);

        return $asList;
    }

    /**
     * @param list<Violation> $chunk
     * @param list<mixed>|null $advisories
     *
     * @return list<Violation>
     */
    private function applyAdvisories(array $chunk, ?array $advisories): array
    {
        if ($advisories === null) {
            return $chunk;
        }

        $out = [];

        foreach ($chunk as $i => $violation) {
            $slot = $advisories[$i] ?? null;

            if (! is_array($slot)) {
                $out[] = $violation;

                continue;
            }

            $advisory = $this->advisoryFromSlot($slot);

            $out[] = $advisory === null ? $violation : $violation->withAdvisory($advisory);
        }

        return $out;
    }

    /**
     * @param array<mixed> $slot
     */
    private function advisoryFromSlot(array $slot): ?ViolationAdvisory
    {
        $summary = isset($slot['summary']) && is_string($slot['summary']) ? $slot['summary'] : '';

        if ($summary === '') {
            return null;
        }

        $impact = isset($slot['impact']) && is_string($slot['impact']) ? $slot['impact'] : '';
        $remediation = isset($slot['remediation']) && is_string($slot['remediation']) ? $slot['remediation'] : '';

        $advSev = null;

        if (isset($slot['advisory_severity']) && is_string($slot['advisory_severity'])) {
            $advSev = Severity::tryFrom($slot['advisory_severity']);
        }

        $confidence = $this->normalizeConfidence($slot['confidence'] ?? null);

        $rationale = isset($slot['rationale']) && is_string($slot['rationale']) ? $slot['rationale'] : '';

        return new ViolationAdvisory(
            summary: $summary,
            impact: $impact,
            remediation: $remediation,
            advisorySeverity: $advSev,
            confidence: $confidence,
            rationale: $rationale,
        );
    }

    private function normalizeConfidence(mixed $value): float|null
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return max(0.0, min(1.0, (float) $value));
        }

        if (is_string($value) && is_numeric($value)) {
            return max(0.0, min(1.0, (float) $value));
        }

        return null;
    }

    private function stringConfig(string $key, string $default): string
    {
        $v = $this->config[$key] ?? $default;

        return is_string($v) ? $v : $default;
    }

    private function intConfig(string $key, int $default): int
    {
        $v = $this->config[$key] ?? $default;

        if (is_int($v)) {
            return $v;
        }

        if (is_string($v) && is_numeric($v)) {
            return (int) $v;
        }

        return $default;
    }
}
