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
            'Clock' => 'Clock',
            'Command' => 'Command',
            'Controller' => 'Controller',
            'Contract' => 'Contract',
            'Exception' => 'Exception',
            'Repository' => 'Repository',
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

    public function testDtoNamespaceDoesNotRepeatDtoSuffix(): void
    {
        $dtoRoot = dirname(__DIR__, 2).'/src/Dto';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dtoRoot));

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            self::assertFalse(
                str_ends_with($file->getBasename('.php'), 'Dto'),
                'The Dto namespace already declares the role; DTO class/file names must not repeat the Dto suffix.',
            );
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
