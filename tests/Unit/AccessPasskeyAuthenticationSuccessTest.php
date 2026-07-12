<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPasskeyAssertionResult;
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
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyAuthenticationSuccessTest extends TestCase
{
    public function testCompletesVerifiedAssertionAndRecordsSuccess(): void
    {
        $user = new AccessEntity('auth@example.test', 'Auth');
        $credential = new AccessPasskeyCredentialEntity($user, 'credential-id', 'handle', 'key', ['internal'], 'Laptop', 2);
        $config = new AccessPasskeyRelyingPartyConfig('example.test', 'Example', 'https://example.test');
        $state = new AccessPasskeyChallengeEntity(
            'challenge',
            AccessPasskeyCeremonyPurpose::Authentication,
            $config->id,
            $config->origin,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 minutes'),
            $user,
        );

        $challenges = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $challenges->method('consume')->willReturn($state);
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn($credential);
        $verifier = $this->createMock(AccessPasskeyAssertionVerifierInterface::class);
        $verifier->method('verify')->willReturn(new AccessPasskeyAssertionResult('credential-id', 'handle', 3));
        $credentials = $this->createMock(AccessPasskeyCredentialServiceInterface::class);
        $credentials->expects(self::once())->method('recordSuccessfulAssertion')->with('credential-id', 3)->willReturn($credential);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::PasskeyAuthenticated,
            AccessSecurityEventSeverity::Info,
            $user,
            null,
            ['credentialName' => 'Laptop'],
        );

        $service = new AccessPasskeyAuthenticationService($challenges, $verifier, $credentials, $repository, $events);

        self::assertSame($user, $service->complete($config, [
            'challenge' => 'challenge',
            'credentialId' => 'credential-id',
        ]));
    }
}
