<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use App\Accessing\Entity\Account;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SignInFlowTest extends WebTestCase
{
    public function testPasswordSignInCompletesForVerifiedAccountWithoutSecondFactor(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $account = new Account('signin@accessing.local', 'Sign In Tester');
        $account->markEmailVerified();
        /** @var AccessingCredentialServiceInterface $credentialService */
        $credentialService = static::getContainer()->get(AccessingCredentialServiceInterface::class);
        $credentialService->createCredential($account, 'signin-pass-123');
        $entityManager->persist($account);
        $entityManager->flush();
        self::ensureKernelShutdown();

        $client = static::createClient();
        $crawler = $client->request('GET', '/sign-in');

        $client->submit($crawler->selectButton('Sign in')->form([
            'account_sign_in_form[emailAddress]' => 'signin@accessing.local',
            'account_sign_in_form[plainPassword]' => 'signin-pass-123',
        ]));

        self::assertResponseRedirects('/overview');
    }
}
