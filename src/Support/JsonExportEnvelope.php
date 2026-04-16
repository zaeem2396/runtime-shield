<?php

declare(strict_types=1);

namespace RuntimeShield\Support;

/**
 * Stable JSON envelope for machine-readable `runtime-shield:export` artifacts.
 */
final class JsonExportEnvelope
{
    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    public static function wrap(string $artifact, array $body): array
    {
        $schemaRaw = config('runtime_shield.dx.export.schema_version', 1);
        $schema = is_int($schemaRaw) ? $schemaRaw : (is_numeric($schemaRaw) ? (int) $schemaRaw : 1);

        return [
            'export_schema_version' => $schema,
            'package_version' => PackageVersion::VERSION,
            'artifact' => $artifact,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'data' => $body,
        ];
    }
}
