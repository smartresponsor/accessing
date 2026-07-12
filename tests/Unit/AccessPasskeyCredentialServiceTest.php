<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\Service\Passkey\AccessPasskeyCredentialService;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyCredentialServiceTest extends TestCase
{
    public function testRegistersUniqueCredential(): void
    {
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn(null);
        $repository->expects(self::once())->method('save')->with(self::isInstanceOf(AccessPasskeyCredentialEntity::class), true);

        $credential = (new AccessPasskeyCredentialService($repository))->register(
            new AccessEntity('passkey@example.test', 'Passkey User'),
            'credential-id',
            'user-handle',
            'public-key',
            ['internal', 'hybrid', 'internal'],
            'Phone',
        );

        self::assertSame(['internal', 'hybrid'], $credential->getTransports());
        self::assertTrue($credential->isActive());
    }

    public function testRejectsDuplicateCredential(): void
    {
        $user = new AccessEntity('duplicate@example.test', 'Duplicate');
        $existing = new AccessPasskeyCredentialEntity($user, 'credential-id', 'handle', 'key', [], 'Existing');
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn($existing);
        $repository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);
        (new AccessPasskeyCredentialService($repository))->register($user, 'credential-id', 'handle', 'key', [], 'Duplicate');
    }

    public function testRejectsNonIncreasingSignCounter(): void
    {
        $credential = new AccessPasskeyCredentialEntity(
            new AccessEntity('counter@example.test', 'Counter'),
            'credential-id',
            'handle',
            'key',
            [],
            'Laptop',
            5,
        );
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn($credential);
        $repository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);
        (new AccessPasskeyCredentialService($repository))->recordSuccessfulAssertion('credential-id', 5);
    }

    public function testAllowsCounterlessAuthenticator(): void
    {
        $credential = new AccessPasskeyCredentialEntity(
            new AccessEntity('counterless@example.test', 'Counterless'),
            'credential-id',
            'handle',
            'key',
            [],
            'Platform',
        );
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn($credential);
        $repository->expects(self::once())->method('save')->with($credential, true);

        (new AccessPasskeyCredentialService($repository))->recordSuccessfulAssertion('credential-id', 0);

        self::assertNotNull($credential->getLastUsedAt());
    }

    public function testRelyingPartyRejectsNonLocalHttpOrigin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AccessPasskeyRelyingPartyConfig('example.test', 'Example', 'http://example.test');
    }

    public function testRelyingPartyAllowsLocalhostHttpOrigin(): void
    {
        $config = new AccessPasskeyRelyingPartyConfig('localhost', 'Local Accessing', 'http://localhost:8000');

        self::assertSame('localhost', $config->id);
    }
}
