<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DTO\AccessPasswordSafetyResultDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\ProviderInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\RepositoryInterface\AccessPersistenceRepositoryInterface;
use App\Accessing\Service\Credential\AccessCredentialService;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessCredentialPasswordSafetyTest extends TestCase
{
    public function testCompromisedPasswordIsRejectedBeforeHashing(): void
    {
        $provider = $this->createMock(AccessCompromisedPasswordProviderInterface::class);
        $provider->expects(self::once())
            ->method('check')
            ->with('compromised-password')
            ->willReturn(new AccessPasswordSafetyResultDTO(AccessPasswordSafetyStatus::Compromised));

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $entityManager = $this->createMock(AccessPersistenceRepositoryInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $service = new AccessCredentialService($hasher, $entityManager, $provider);

        $this->expectException(AccessCompromisedPasswordException::class);
        $service->createCredential(new AccessEntity(), 'compromised-password');
    }

    public function testUnavailableProviderFailsClosedBeforeHashing(): void
    {
        $provider = $this->createMock(AccessCompromisedPasswordProviderInterface::class);
        $provider->expects(self::once())
            ->method('check')
            ->with('candidate-password')
            ->willReturn(new AccessPasswordSafetyResultDTO(AccessPasswordSafetyStatus::Unavailable));

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $entityManager = $this->createMock(AccessPersistenceRepositoryInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $service = new AccessCredentialService($hasher, $entityManager, $provider);

        $this->expectException(AccessPasswordSafetyUnavailableException::class);
        $service->createCredential(new AccessEntity(), 'candidate-password');
    }

    public function testVerifyPasswordRequiresCredentialAndDelegatesToHasher(): void
    {
        $provider = $this->createMock(AccessCompromisedPasswordProviderInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $entityManager = $this->createMock(AccessPersistenceRepositoryInterface::class);
        $service = new AccessCredentialService($hasher, $entityManager, $provider);

        $withoutCredential = new AccessEntity('missing-credential@example.test');
        $hasher->expects(self::exactly(2))
            ->method('isPasswordValid')
            ->willReturnOnConsecutiveCalls(true, false);
        self::assertFalse($service->verifyPassword($withoutCredential, 'password'));

        $user = new AccessEntity('verify-credential@example.test');
        $user->setCredential(new \App\Accessing\Entity\AccessCredentialEntity($user, 'hash'));
        self::assertTrue($service->verifyPassword($user, 'correct'));
        self::assertFalse($service->verifyPassword($user, 'wrong'));
    }

    public function testChangePasswordUpdatesExistingCredential(): void
    {
        $provider = $this->createMock(AccessCompromisedPasswordProviderInterface::class);
        $provider->expects(self::once())->method('check')->with('replacement-password')->willReturn(
            new AccessPasswordSafetyResultDTO(AccessPasswordSafetyStatus::Safe),
        );
        $user = new AccessEntity('existing-credential@example.test');
        $credential = new \App\Accessing\Entity\AccessCredentialEntity($user, 'old-hash');
        $user->setCredential($credential);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')->with($user, 'replacement-password')->willReturn('new-hash');
        $entityManager = $this->createMock(AccessPersistenceRepositoryInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($credential);
        $entityManager->expects(self::once())->method('flush');

        (new AccessCredentialService($hasher, $entityManager, $provider))->changePassword($user, 'replacement-password');
        self::assertSame('new-hash', $credential->getPasswordHash());
    }

    public function testChangePasswordCreatesLegacyCredentialOnlyOnce(): void
    {
        $provider = $this->createMock(AccessCompromisedPasswordProviderInterface::class);
        $provider->expects(self::once())
            ->method('check')
            ->with('replacement-password')
            ->willReturn(new AccessPasswordSafetyResultDTO(AccessPasswordSafetyStatus::Safe));

        $user = new AccessEntity();
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())
            ->method('hashPassword')
            ->with($user, 'replacement-password')
            ->willReturn('password-hash');

        $entityManager = $this->createMock(AccessPersistenceRepositoryInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $service = new AccessCredentialService($hasher, $entityManager, $provider);
        $service->changePassword($user, 'replacement-password');

        self::assertSame('password-hash', $user->getPasswordHash());
    }
}
