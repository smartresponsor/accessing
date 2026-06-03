<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccessingOperatorRoutesTest extends WebTestCase
{
    /**
     * @return array{0: AccessAccountEntity, 1: AccessAccountEntity}
     */
    private function createSupportAndTargetAccounts(): array
    {
        $supportAccount = new AccessAccountEntity('support@accessing.local', 'Support Tester');
        $supportAccount->setRoles(['ROLE_SUPPORT']);
        $supportAccount->setPasswordHash('support-password');

        $targetAccount = new AccessAccountEntity('target@accessing.local', 'Target Tester');
        $targetAccount->setRoles(['ROLE_ACCOUNT']);
        $targetAccount->setPhoneNumber('+15555550124');
        $targetAccount->markEmailVerified();
        $targetAccount->markPhoneVerified();
        $targetAccount->setSecondFactorEnabled(true);

        $this->setEntityId($supportAccount, 1);
        $this->setEntityId($targetAccount, 42);

        return [$supportAccount, $targetAccount];
    }

    private function setEntityId(AccessAccountEntity $account, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($account, 'id');
        $reflectionProperty->setValue($account, $id);
    }

    private function stubRepositories(AccessAccountEntity $targetAccount): void
    {
        static::getContainer()->set(AccountRepositoryInterface::class, new class($targetAccount) implements AccountRepositoryInterface {
            public function __construct(private readonly AccessAccountEntity $targetAccount)
            {
            }

            public function save(AccessAccountEntity $account, bool $flush = false): void
            {
            }

            public function remove(AccessAccountEntity $account, bool $flush = false): void
            {
            }

            public function findById(int $id): ?AccessAccountEntity
            {
                return 42 === $id ? $this->targetAccount : null;
            }

            public function findOneByEmailAddress(string $emailAddress): ?AccessAccountEntity
            {
                return null;
            }

            public function findRecentAccounts(int $limit = 20): array
            {
                return [$this->targetAccount];
            }
        });

        static::getContainer()->set(SecurityEventRepositoryInterface::class, new class implements SecurityEventRepositoryInterface {
            public function save(AccessSecurityEventEntity $securityEvent, bool $flush = false): void
            {
            }

            public function findRecentEvents(int $limit = 50): array
            {
                return [];
            }

            public function findRecentEventsForAccount(AccessAccountEntity $account, int $limit = 50): array
            {
                return [];
            }
        });
    }

    private function stubAccountProvider(AccessAccountEntity $account): void
    {
        static::getContainer()->set('security.user.provider.concrete.accessing_account_provider', new class($account) implements \Symfony\Component\Security\Core\User\UserProviderInterface {
            public function __construct(private readonly AccessAccountEntity $account)
            {
            }

            public function refreshUser(UserInterface $user): UserInterface
            {
                if (!$user instanceof AccessAccountEntity) {
                    throw new \Symfony\Component\Security\Core\Exception\UnsupportedUserException();
                }

                return $this->account;
            }

            public function supportsClass(string $class): bool
            {
                return is_a($class, AccessAccountEntity::class, true);
            }

            public function loadUserByIdentifier(string $identifier): UserInterface
            {
                return $this->account;
            }
        });
    }

    public function testOperatorAccountsPageIsSuccessfulForSupport(): void
    {
        $client = static::createClient();
        [$supportAccount, $targetAccount] = $this->createSupportAndTargetAccounts();
        $this->stubRepositories($targetAccount);
        $this->stubAccountProvider($supportAccount);
        $client->loginUser($supportAccount);
        $client->request('GET', '/accessing/operator/account');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Operator account view');
    }

    public function testOperatorSecurityEventsPageIsSuccessfulForSupport(): void
    {
        $client = static::createClient();
        [$supportAccount, $targetAccount] = $this->createSupportAndTargetAccounts();
        $this->stubRepositories($targetAccount);
        $this->stubAccountProvider($supportAccount);
        $client->loginUser($supportAccount);
        $client->request('GET', '/accessing/operator/security/event');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Global security events');
    }

    public function testOperatorAccountDetailPageIsSuccessfulForSupport(): void
    {
        $client = static::createClient();
        [$supportAccount, $targetAccount] = $this->createSupportAndTargetAccounts();
        $this->stubRepositories($targetAccount);
        $this->stubAccountProvider($supportAccount);
        $client->loginUser($supportAccount);
        $client->request('GET', sprintf('/accessing/operator/account/%d', $targetAccount->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'target@accessing.local');
    }
}
