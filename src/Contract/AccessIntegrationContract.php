<?php

declare(strict_types=1);

namespace App\Accessing\Contract;

/**
 * Accessing's integration contract.
 *
 * Accessing owns only access-domain identity metadata here.
 * Administration/workbench contracts were evacuated from this component.
 */
final readonly class AccessIntegrationContract
{
    /** What Accessing owns in the ecosystem. */
    public string $owns;

    /**
     * Prefix used when building ACL subject identifiers.
     * Format: '{prefix}{userId}'.
     */
    public string $subjectPrefix;

    public function __construct(
        string $owns,
        string $subjectPrefix,
    ) {
        $this->owns = $owns;
        $this->subjectPrefix = $subjectPrefix;
    }

    /**
     * Build from the 'integration' section of Accessing's component.yaml.
     *
     * @param array<string, mixed> $data
     */
    public static function fromYaml(array $data): self
    {
        return new self(
            owns: self::stringValue($data['owns'] ?? null, 'authentication_session_identity'),
            subjectPrefix: self::stringValue($data['subject_prefix'] ?? null, 'accessing:user:'),
        );
    }

    private static function stringValue(mixed $value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || null === $value) {
            return (string) ($value ?? $default);
        }

        return $default;
    }
}
