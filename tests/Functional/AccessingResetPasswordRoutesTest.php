<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

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
        $client->request('GET', '/forgot-password');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Forgot password');
    }

    public function testResetPasswordCheckEmailPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forgot-password/check-email');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Reset request received');
    }

    public function testResetPasswordCheckEmailPostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/forgot-password/check-email');

        self::assertResponseStatusCodeSame(405);
    }

    public function testInvalidResetTokenRedirectsThroughPlainResetRoute(): void
    {
        $this->prepareSchema();

        $client = static::createClient();
        $client->request('GET', '/forgot-password/reset/invalid-token');

        self::assertResponseRedirects('/forgot-password/reset');

        $client->followRedirect();
        self::assertResponseRedirects('/forgot-password');
    }

    public function testInvalidResetTokenPostRedirectsThroughPlainResetRoute(): void
    {
        $this->prepareSchema();

        $client = static::createClient();
        $client->request('POST', '/forgot-password/reset/invalid-token');

        self::assertResponseRedirects('/forgot-password/reset');
    }
}
