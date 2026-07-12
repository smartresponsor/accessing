<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AccessResetPasswordTokenExposureTest extends TestCase
{
    public function testResetTokenPreviewIsRestrictedToDevelopmentAndTestEnvironments(): void
    {
        $source = file_get_contents(__DIR__.'/../../src/Service/Http/Access/AccessResetPasswordFlowService.php');

        self::assertIsString($source);
        self::assertStringContainsString(
            "in_array(\$this->kernel->getEnvironment(), ['dev', 'test'], true)",
            $source,
        );
        self::assertStringContainsString("['token' => \$resetToken->getToken()]", $source);
        self::assertStringNotContainsString("'prod' => \$resetToken->getToken()", $source);
    }

    public function testPasswordMutationCodeDoesNotLogOrPersistProviderFailureDetails(): void
    {
        $providerSource = file_get_contents(__DIR__.'/../../src/Service/Password/AccessSymfonyCompromisedPasswordProvider.php');

        self::assertIsString($providerSource);
        self::assertStringContainsString('catch (\\Throwable)', $providerSource);
        self::assertStringNotContainsString('getMessage()', $providerSource);
        self::assertStringNotContainsString('logger', mb_strtolower($providerSource));
    }
}
