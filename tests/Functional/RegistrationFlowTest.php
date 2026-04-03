<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationFlowTest extends WebTestCase
{
    public function testRegistrationCreatesAnAccountAndRedirectsToSignIn(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        self::ensureKernelShutdown();

        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $client->submit($crawler->selectButton('Register')->form([
            'account_registration_form[displayName]' => 'Functional Tester',
            'account_registration_form[emailAddress]' => 'functional@accessing.local',
            'account_registration_form[plainPassword]' => 'functional-pass-123',
        ]));

        self::assertResponseRedirects('/sign-in');
        $client->followRedirect();
        self::assertSelectorExists('.alert-success');

        /** @var AccountRepository $accountRepository */
        $accountRepository = static::getContainer()->get(AccountRepository::class);
        self::assertNotNull($accountRepository->findOneByEmailAddress('functional@accessing.local'));
    }
}
