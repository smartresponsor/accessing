<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPasswordSafetyResult;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\ProviderInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\Service\Credential\AccessCredentialService;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;
use Doctrine\ORM\EntityManagerInterface;
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
            ->willReturn(new AccessPasswordSafetyResult(AccessPasswordSafetyStatus::Compromised));

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $entityManager = $this->createMock(EntityManagerInterface::class);
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
            ->willReturn(new AccessPasswordSafetyResult(AccessPasswordSafetyStatus::Unavailable));

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $service = new AccessCredentialService($hasher, $entityManager, $provider);

        $this->expectException(AccessPasswordSafetyUnavailableException::class);
        $service->createCredential(new AccessEntity(), 'candidate-password');
    }

    public function testChangePasswordCreatesLegacyCredentialOnlyOnce(): void
    {
        $provider = $this->createMock(AccessCompromisedPasswordProviderInterface::class);
        $provider->expects(self::once())
            ->method('check')
            ->with('replacement-password')
            ->willReturn(new AccessPasswordSafetyResult(AccessPasswordSafetyStatus::Safe));

        $user = new AccessEntity();
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())
            ->method('hashPassword')
            ->with($user, 'replacement-password')
            ->willReturn('password-hash');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $service = new AccessCredentialService($hasher, $entityManager, $provider);
        $service->changePassword($user, 'replacement-password');

        self::assertSame('password-hash', $user->getPasswordHash());
    }
}
