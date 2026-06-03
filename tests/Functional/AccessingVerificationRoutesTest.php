<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use App\Accessing\Dto\AccessingIssuedChallengeDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class AccessingVerificationRoutesTest extends WebTestCase
{
    private function createAuthenticatedAccount(): AccessAccountEntity
    {
        $account = new AccessAccountEntity('verify@accessing.local', 'Verify Tester');
        $account->setRoles(['ROLE_ACCOUNT']);
        $account->setPhoneNumber('+15555550123');
        $account->markEmailVerified();
        $this->setEntityId($account, 7);

        return $account;
    }

    private function setEntityId(AccessAccountEntity $account, int $id): void
    {
        $reflectionProperty = new \ReflectionProperty($account, 'id');
        $reflectionProperty->setValue($account, $id);
    }

    private function stubVerificationService(): void
    {
        static::getContainer()->set(AccessingVerificationChallengeServiceInterface::class, new class implements AccessingVerificationChallengeServiceInterface {
            public function issueEmailVerification(AccessAccountEntity $account, ?\Symfony\Component\HttpFoundation\Request $request = null): AccessingIssuedChallengeDto
            {
                throw new \LogicException('Not used in this smoke test.');
            }

            public function issuePhoneVerification(AccessAccountEntity $account, string $phoneNumber, ?\Symfony\Component\HttpFoundation\Request $request = null): AccessingIssuedChallengeDto
            {
                throw new \LogicException('Not used in this smoke test.');
            }

            public function issuePasswordRecovery(AccessAccountEntity $account, ?\Symfony\Component\HttpFoundation\Request $request = null): AccessingIssuedChallengeDto
            {
                throw new \LogicException('Not used in this smoke test.');
            }

            public function completeEmailVerification(AccessAccountEntity $account, string $code): bool
            {
                throw new \LogicException('Not used in this smoke test.');
            }

            public function completePhoneVerification(AccessAccountEntity $account, string $code): bool
            {
                throw new \LogicException('Not used in this smoke test.');
            }

            public function consumePasswordRecovery(AccessAccountEntity $account, string $code): bool
            {
                throw new \LogicException('Not used in this smoke test.');
            }

            public function cleanupExpiredChallenges(): int
            {
                throw new \LogicException('Not used in this smoke test.');
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

    public function testVerifyEmailPageIsSuccessful(): void
    {
        $client = static::createClient();
        $account = $this->createAuthenticatedAccount();
        $this->stubVerificationService();
        $this->stubAccountProvider($account);
        $client->loginUser($account);
        $client->request('GET', '/accessing/verify/email');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Verify email ownership');
    }

    public function testVerifyPhoneRequestPageIsSuccessful(): void
    {
        $client = static::createClient();
        $account = $this->createAuthenticatedAccount();
        $this->stubVerificationService();
        $this->stubAccountProvider($account);
        $client->loginUser($account);
        $client->request('GET', '/accessing/verify/phone');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Request phone verification');
    }

    public function testVerifyPhoneConfirmPageIsSuccessful(): void
    {
        $client = static::createClient();
        $account = $this->createAuthenticatedAccount();
        $this->stubVerificationService();
        $this->stubAccountProvider($account);
        $client->loginUser($account);
        $client->request('GET', '/accessing/verify/phone/confirm');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Confirm phone verification');
    }
}
