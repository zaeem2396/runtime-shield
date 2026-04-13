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
        if ($chunk === []) {
            return [];
        }

        $baseUrl = rtrim($this->stringConfig('base_url', 'https://api.openai.com/v1'), '/');
        $model = $this->stringConfig('model', 'gpt-4o-mini');
        $n = count($chunk);
        $timeoutMs = $this->effectiveTimeoutMs($n);
        $maxTokens = $this->effectiveMaxTokens($n);

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
                'body_prefix' => $this->safeBodyPrefix($response->body),
            ]);

            return $chunk;
        }

        $decoded = $this->decodeJsonObject($response->body);

        if ($decoded === null) {
            $this->logger?->notice(
                'RuntimeShield AI response body is not valid JSON',
                ['body_prefix' => $this->safeBodyPrefix($response->body)],
            );

            return $chunk;
        }

        if (isset($decoded['error'])) {
            $this->logger?->warning(
                'RuntimeShield AI API error: ' . $this->formatOpenAiError($decoded['error']),
                [],
            );

            return $chunk;
        }

        $advisories = $this->parseAdvisoriesFromCompletionEnvelope($decoded);

        if ($advisories === null) {
            $this->logger?->notice(
                'RuntimeShield AI advisory response could not be parsed (invalid message content or truncated output). '
                . 'Try increasing RUNTIME_SHIELD_AI_MAX_TOKENS or lowering RUNTIME_SHIELD_AI_BATCH_SIZE.',
                ['violations_in_chunk' => $n],
            );
        }

        return $this->applyAdvisories($chunk, $advisories);
    }

    /**
     * Batched Chat Completions need far more than a few seconds; short timeouts produced
     * empty or partial HTTP bodies (no advisories) while still returning HTTP 200 in some setups.
     */
    private function effectiveTimeoutMs(int $violationCount): int
    {
        $n = max(1, $violationCount);
        $configured = max(100, $this->intConfig('timeout_ms', 60_000));
        $floor = (int) min(180_000, 4_000 + $n * 2_000);

        return max($configured, $floor);
    }

    /**
     * Ensure output budget scales with batch size even when .env still has a low cap.
     */
    private function effectiveMaxTokens(int $violationCount): int
    {
        $n = max(1, $violationCount);
        $configured = max(64, $this->intConfig('max_tokens', 4096));
        $floor = (int) min(16_384, $n * 280);

        return max($configured, $floor);
    }

    private function safeBodyPrefix(string $body): string
    {
        $trimmed = trim($body);

        return strlen($trimmed) <= 240 ? $trimmed : substr($trimmed, 0, 240) . '…';
    }

    private function formatOpenAiError(mixed $error): string
    {
        if (is_array($error)) {
            $msg = $error['message'] ?? null;

            return is_string($msg) ? $msg : (string) json_encode($error);
        }

        return is_string($error) ? $error : (string) json_encode($error);
    }

    /**
     * @return array<mixed, mixed>|null
     */
    private function decodeJsonObject(string $body): ?array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<mixed, mixed> $decoded
     *
     * @return list<mixed>|null
     */
    private function parseAdvisoriesFromCompletionEnvelope(array $decoded): ?array
    {
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

        $text = $this->normalizeAssistantContent($message['content'] ?? null);

        if ($text === null || $text === '') {
            return null;
        }

        try {
            /** @var array<string, mixed> $inner */
            $inner = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
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

    private function normalizeAssistantContent(mixed $content): ?string
    {
        if (is_string($content)) {
            return $this->stripOptionalMarkdownFences(trim($content));
        }

        if (! is_array($content)) {
            return null;
        }

        $parts = [];

        foreach ($content as $part) {
            if (! is_array($part)) {
                continue;
            }

            if (($part['type'] ?? '') === 'text' && isset($part['text']) && is_string($part['text'])) {
                $parts[] = $part['text'];
            }
        }

        $joined = trim(implode('', $parts));

        return $joined === '' ? null : $this->stripOptionalMarkdownFences($joined);
    }

    private function stripOptionalMarkdownFences(string $s): string
    {
        $s = trim($s);

        if ($s === '' || ! str_starts_with($s, '```')) {
            return $s;
        }

        $s = preg_replace('/^```(?:json)?\s*/i', '', $s) ?? $s;
        $s = preg_replace('/\s*```\s*$/', '', $s) ?? $s;

        return trim($s);
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
