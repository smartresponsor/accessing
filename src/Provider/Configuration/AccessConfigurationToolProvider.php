<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Configuration;

use App\Accessing\Contract\AccessIntegrationContract;
use App\Accessing\Service\Config\AccessEnvironmentConfigService;
use App\Configuring\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Configuring\Value\Tool\ConfigurationToolDefinition;
use Symfony\Component\Yaml\Yaml;

/**
 * Accessing's self-registration with Administering.
 *
 * Follows the canonical pattern established in RollingConfigurationToolProvider.
 * See docs/architecture/034-component-integration-contract-pattern.adoc.
 *
 * Responsibilities:
 *  - Declare what tools Accessing exposes to the EasyAdmin surface.
 *  - Expose Accessing's typed integration contract so Administering does NOT
 *    hardcode it in its own component.yaml.
 *
 * Tag: configuring.configuration_tool_provider
 * Dynamic forms are built by Administering from Configuring managed variables.
 */
final class AccessConfigurationToolProvider implements ConfigurationToolProviderInterface
{
    // ------------------------------------------------------------------
    // ConfigurationToolProviderInterface
    // ------------------------------------------------------------------

    public function componentKey(): string
    {
        return 'Accessing';
    }

    public function componentToken(): string
    {
        return 'accessing';
    }

    /**
     * Tools Accessing exposes in the Administering EasyAdmin.
     *
     * @return iterable<ConfigurationToolDefinition>
     */
    public function tools(): iterable
    {
        // Tool: Environment configuration
        // Allows operators to adjust Accessing's runtime flags (mailer sender,
        // phone verification provider, session and lock thresholds) without
        // a code deploy.
        yield new ConfigurationToolDefinition(
            componentKey: $this->componentKey(),
            componentToken: $this->componentToken(),
            toolSlug: 'Environment',
            serviceClass: AccessEnvironmentConfigService::class,
            serviceShortName: 'AccessEnvironmentConfigService',
            label: 'Accessing Environment',
            executable: true,
        );

        // Add more tools here as Accessing grows.
        // Each yield is a new EasyAdmin tool row — zero Administering changes.
    }

    // ------------------------------------------------------------------
    // Integration contract
    // ------------------------------------------------------------------

    /**
     * Accessing's typed integration contract.
     *
     * Loaded from Accessing's OWN component.yaml — the source of truth
     * stays in Accessing's repository, not in Administering's.
     */
    public function integrationContract(): AccessIntegrationContract
    {
        return AccessIntegrationContract::fromYaml(
            $this->integrationSection()
        );
    }

    // ------------------------------------------------------------------
    // Private
    // ------------------------------------------------------------------

    /**
     * Returns the 'integration' section from Accessing's component.yaml.
     *
     * Note: Accessing uses 'integration' (singular) as the top-level key,
     * matching its schema_version: 1 format. Rolling uses 'integrations.rolling'.
     * Both patterns are valid — the provider adapts to its own yaml shape.
     *
     * @return array<string, mixed>
     */
    private function integrationSection(): array
    {
        $path = dirname(__DIR__, 3).'/config/component/component.yaml';

        if (!is_file($path)) {
            return [];
        }

        $parsed = Yaml::parseFile($path);
        if (!is_array($parsed)) {
            return [];
        }

        // Support both flat key ('integration') and namespaced ('integrations.accessing')
        $section = $parsed['integration'] ?? null;
        if (!is_array($section)) {
            $integrations = $parsed['integrations'] ?? null;
            $section = is_array($integrations) ? ($integrations['accessing'] ?? null) : null;
        }

        return is_array($section) ? self::stringKeyMap($section) : [];
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function stringKeyMap(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
