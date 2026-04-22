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

        $container->prependExtensionConfig('framework', $config['framework']);

        $twigConfigFile = __DIR__.'/../../config/packages/accessing_twig.yaml';
        if (!is_file($twigConfigFile)) {
            return;
        }

        $twigConfig = Yaml::parseFile($twigConfigFile);
        if (!is_array($twigConfig) || !isset($twigConfig['twig']) || !is_array($twigConfig['twig'])) {
            return;
        }

        $twig = $twigConfig['twig'];
        $twig['paths'] = [dirname(__DIR__, 2).'/templates' => null] + ($twig['paths'] ?? []);

        $container->prependExtensionConfig('twig', $twig);
    }
}
