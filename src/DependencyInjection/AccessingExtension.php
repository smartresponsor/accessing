<?php

declare(strict_types=1);

namespace App\Accessing\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads the Symfony-native service export for the Accessing RC component.
 */
final class AccessingExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array<int, array<string, mixed>> $configs
     *
     * @throws \Exception when the component service configuration cannot be loaded
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        unset($configs);

        $configDirectory = __DIR__.'/../../config/component';
        $runtimeConfigFile = $configDirectory.'/runtime.yaml';
        if (is_file($runtimeConfigFile)) {
            $runtimeConfig = Yaml::parseFile($runtimeConfigFile);
            if (is_array($runtimeConfig)) {
                $this->applyRuntimeParameters($container, self::stringKeyMap($runtimeConfig));
            }
        }

        $servicesFile = $configDirectory.'/services.yaml';

        if (!is_file($servicesFile)) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator($configDirectory));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $frameworkConfigFile = __DIR__.'/../../config/packages/accessing_rate_limiter.yaml';
        if (!is_file($frameworkConfigFile)) {
            return;
        }

        $config = Yaml::parseFile($frameworkConfigFile);
        if (!is_array($config) || !isset($config['framework']) || !is_array($config['framework'])) {
            return;
        }

        $container->prependExtensionConfig('framework', self::stringKeyMap($config['framework']));

        $twigConfigFile = __DIR__.'/../../config/packages/accessing_twig.yaml';
        if (!is_file($twigConfigFile)) {
            return;
        }

        $twigConfig = Yaml::parseFile($twigConfigFile);
        if (!is_array($twigConfig) || !isset($twigConfig['twig']) || !is_array($twigConfig['twig'])) {
            return;
        }

        $twig = self::stringKeyMap($twigConfig['twig']);
        $paths = $twig['paths'] ?? [];
        $twig['paths'] = [dirname(__DIR__, 2).'/templates' => null] + (is_array($paths) ? self::stringKeyMap($paths) : []);

        $container->prependExtensionConfig('twig', $twig);
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

    /**
     * @param array<string, mixed> $runtimeConfig
     */
    private function applyRuntimeParameters(ContainerBuilder $container, array $runtimeConfig): void
    {
        $map = [
            'accessing.mailer_sender' => 'accessing_mailer_sender',
            'accessing.phone_verification_provider' => 'accessing_phone_verification_provider',
            'accessing.session_max_idle_days' => 'accessing_session_max_idle_days',
            'accessing.recovery_code_ttl_minutes' => 'accessing_recovery_code_ttl_minutes',
            'accessing.verification_code_ttl_minutes' => 'accessing_verification_code_ttl_minutes',
            'accessing.user_lock_threshold' => 'accessing_user_lock_threshold',
            'accessing.user_lock_minutes' => 'accessing_user_lock_minutes',
        ];

        foreach ($map as $parameterName => $runtimeKey) {
            if (!array_key_exists($runtimeKey, $runtimeConfig)) {
                continue;
            }

            $value = $runtimeConfig[$runtimeKey];
            if (is_scalar($value) || null === $value) {
                $container->setParameter($parameterName, $value);
            }
        }
    }
}
