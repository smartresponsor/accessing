<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingResetPasswordRoutesTest extends WebTestCase
{
    private function prepareSchema(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if ([] !== $metadata) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        self::ensureKernelShutdown();
    }

    public function testResetPasswordRequestPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Reset password');
    }

    public function testResetPasswordCheckEmailPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/check-email');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Reset request received');
    }

    public function testResetPasswordCheckEmailPostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/reset-password/check-email');

        self::assertResponseStatusCodeSame(405);
    }

    public function testInvalidResetTokenRedirectsThroughPlainResetRoute(): void
    {
        $this->prepareSchema();

        $client = static::createClient();
        $client->request('GET', '/reset-password/reset/invalid-token');

        self::assertResponseRedirects('/reset-password/reset');

        $client->followRedirect();
        self::assertResponseRedirects('/reset-password');
    }

    public function testInvalidResetTokenPostRedirectsThroughPlainResetRoute(): void
    {
        $this->prepareSchema();

        $client = static::createClient();
        $client->request('POST', '/reset-password/reset/invalid-token');

        self::assertResponseRedirects('/reset-password/reset');
    }
}
