<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AccessArchitectureConventionTest extends TestCase
{
    public function testCanonicalInterfaceMirrorDirectoriesExist(): void
    {
        $src = dirname(__DIR__, 2).'/src';

        foreach ([
            'ServiceInterface',
            'RepositoryInterface',
            'ProviderInterface',
            'FactoryInterface',
            'ResponderInterface',
            'RecorderInterface',
            'VerifierInterface',
        ] as $directory) {
            self::assertDirectoryExists($src.'/'.$directory);
        }
    }

    public function testAmbiguousValueDirectoryDoesNotExist(): void
    {
        self::assertDirectoryDoesNotExist(dirname(__DIR__, 2).'/src/Value');
    }

    public function testInterfaceDirectoryTreesMirrorImplementationDirectoryTrees(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $mirrors = [
            'ServiceInterface' => 'Service',
            'RepositoryInterface' => 'Repository',
            'ProviderInterface' => 'Provider',
            'FactoryInterface' => 'Factory',
            'ResponderInterface' => 'Responder',
            'RecorderInterface' => 'Recorder',
            'VerifierInterface' => 'Verifier',
        ];

        foreach ($mirrors as $interfaceDirectory => $implementationDirectory) {
            $interfaceRoot = $src.'/'.$interfaceDirectory;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($interfaceRoot, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST,
            );

            foreach ($iterator as $entry) {
                if (!$entry instanceof \SplFileInfo || !$entry->isDir()) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($interfaceRoot) + 1));
                self::assertDirectoryExists($src.'/'.$implementationDirectory.'/'.$relative);
            }
        }
    }

    public function testClassRoleMatchesCanonicalTopLevelDirectory(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src));

        $roles = [
            'Authenticator' => 'Authenticator',
            'Builder' => 'Builder',
            'Clock' => 'Clock',
            'Codec' => 'Codec',
            'Command' => 'Command',
            'Controller' => 'Controller',
            'Exception' => 'Exception',
            'Factory' => 'Factory',
            'Policy' => 'Policy',
            'Provider' => 'Provider',
            'Recorder' => 'Recorder',
            'Repository' => 'Repository',
            'Resolver' => 'Resolver',
            'Responder' => 'Responder',
            'Service' => 'Service',
            'Verifier' => 'Verifier',
        ];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $basename = $file->getBasename('.php');
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($src) + 1));

            foreach ($roles as $suffix => $directory) {
                if (!str_ends_with($basename, $suffix)) {
                    continue;
                }

                self::assertStringStartsWith(
                    $directory.'/',
                    $relative,
                    sprintf('%s must live under src/%s.', $basename, $directory),
                );
            }
        }
    }

    public function testDtoConventionIsExplicit(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $dtoRoot = $src.'/DTO';

        self::assertDirectoryExists($dtoRoot);

        $topLevelEntries = scandir($src);
        self::assertIsArray($topLevelEntries);
        self::assertNotContains(
            'Dto',
            $topLevelEntries,
            'Canon003 requires the literal top-level DTO directory casing.',
        );

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dtoRoot));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            self::assertStringEndsWith(
                'DTO',
                $file->getBasename('.php'),
                'Canon003 requires every DTO filename/type to use the exact DTO suffix.',
            );
        }
    }

    public function testSubjectDirectoryIsNotRepeatedPrematurely(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($src) + 1));
            $parts = explode('/', $relative);
            self::assertFalse(
                isset($parts[1]) && 'Access' === $parts[1] && 'Entity' !== $parts[0],
                sprintf('Canon004 forbids premature Access/ subject directory in %s.', $relative),
            );
        }
    }

    public function testCanonicalPackagingAndGeneratedArtifacts(): void
    {
        $root = dirname(__DIR__, 2);
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $prod = json_decode((string) file_get_contents($root.'/composer.prod.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($composer);
        self::assertIsArray($prod);
        self::assertIsArray($composer['require'] ?? null);
        self::assertIsArray($composer['autoload'] ?? null);
        self::assertIsArray($composer['autoload']['psr-4'] ?? null);
        self::assertIsArray($prod['autoload'] ?? null);
        self::assertIsArray($prod['autoload']['psr-4'] ?? null);
        self::assertIsArray($prod['repositories'] ?? null);

        foreach (['cruding/crud', 'viewing/view', 'interfacing/interface', 'objecting/object', 'easycorp/easyadmin-bundle'] as $package) {
            self::assertArrayHasKey($package, $composer['require']);
        }
        self::assertSame($composer['name'], $prod['name']);
        self::assertSame($composer['type'], $prod['type']);
        self::assertSame($composer['autoload']['psr-4'], $prod['autoload']['psr-4']);
        foreach ($prod['repositories'] as $repository) {
            self::assertIsArray($repository);
            self::assertNotSame('path', $repository['type'] ?? null, 'Canon024 forbids path repositories in composer.prod.json.');
        }

        $trackedReference = [];
        $gitExitCode = 0;
        exec(sprintf('git -C %s ls-files --error-unmatch -- config/reference.php 2>&1', escapeshellarg($root)), $trackedReference, $gitExitCode);
        self::assertNotSame(
            0,
            $gitExitCode,
            'Canon037 permits a locally generated config/reference.php but forbids tracking it as repository source.',
        );
        self::assertStringContainsString('/config/reference.php', (string) file_get_contents($root.'/.gitignore'));
    }

    public function testComponentOwnedYamlUsesAccessPrefix(): void
    {
        $root = dirname(__DIR__, 2);
        $bootstrap = ['annotations.yaml', 'api_platform.yaml', 'asset_mapper.yaml', 'cache.yaml', 'controllers.yaml', 'csrf.yaml', 'doctrine.yaml', 'doctrine_migrations.yaml', 'easyadmin.yaml', 'framework.yaml', 'lock.yaml', 'mailer.yaml', 'messenger.yaml', 'monolog.yaml', 'nelmio_api_doc.yaml', 'notifier.yaml', 'property_info.yaml', 'rate_limiter.yaml', 'reset_password.yaml', 'routes.yaml', 'routing.yaml', 'scheb_2fa.yaml', 'security.yaml', 'services.yaml', 'translation.yaml', 'twig.yaml', 'twig_component.yaml', 'ux_turbo.yaml', 'validator.yaml', 'verify_email.yaml', 'web_profiler.yaml'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/config', \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || !in_array(strtolower($file->getExtension()), ['yaml', 'yml'], true)) {
                continue;
            }
            $name = strtolower($file->getBasename());
            if (in_array(preg_replace('/\.yml$/', '.yaml', $name), $bootstrap, true) || 1 === preg_match('/^(?:services|routes)_(?:dev|test|prod)\.ya?ml$/', $name)) {
                continue;
            }
            self::assertStringStartsWith('access_', $name, sprintf('Canon038 requires access_ prefix for %s.', $file->getPathname()));
        }
    }

    public function testApiClassesKeepAccessComponentPrefixFirst(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            self::assertFalse(
                str_starts_with($file->getBasename('.php'), 'ApiAccess'),
                'API classes must follow the canonical AccessApi* component-subject-role order.',
            );
        }
    }

    public function testProviderServiceDoubleRoleNameIsForbidden(): void
    {
        $src = dirname(__DIR__, 2).'/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            self::assertStringNotContainsString(
                'ProviderService',
                $file->getBasename('.php'),
                'A class must have one canonical role suffix, not ProviderService.',
            );
        }
    }
}
