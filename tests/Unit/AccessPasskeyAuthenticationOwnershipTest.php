<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\Service\Passkey\AccessPasskeyAuthenticationService;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAssertionVerifierInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyAuthenticationOwnershipTest extends TestCase
{
    public function testRejectsCredentialOwnedByAnotherUser(): void
    {
        $config = new AccessPasskeyRelyingPartyConfig('example.test', 'Example', 'https://example.test');
        $challengeUser = new AccessEntity('challenge@example.test', 'Challenge');
        $credentialUser = new AccessEntity('credential@example.test', 'Credential');
        $state = new AccessPasskeyChallengeEntity('challenge', AccessPasskeyCeremonyPurpose::Authentication, $config->id, $config->origin, new \DateTimeImmutable(), new \DateTimeImmutable('+5 minutes'), $challengeUser);
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn(new AccessPasskeyCredentialEntity($credentialUser, 'credential-id', 'handle', 'key', [], 'Key'));
        $challenges = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $challenges->method('consume')->willReturn($state);
        $service = new AccessPasskeyAuthenticationService($challenges, $this->createMock(AccessPasskeyAssertionVerifierInterface::class), $this->createMock(AccessPasskeyCredentialServiceInterface::class), $repository, $this->createMock(AccessSecurityEventServiceInterface::class));
        $this->expectException(\DomainException::class);
        $service->complete($config, ['challenge' => 'challenge', 'credentialId' => 'credential-id']);
    }
}
