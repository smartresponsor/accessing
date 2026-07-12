<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPasskeyAttestationResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\Service\Passkey\AccessPasskeyRegistrationService;
use App\Accessing\Service\Passkey\AccessUnavailablePasskeyAttestationVerifier;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAttestationVerifierInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyRegistrationServiceTest extends TestCase
{
    public function testIssuesBrowserRegistrationOptionsWithExistingCredentialsExcluded(): void
    {
        $user = new AccessEntity('options@example.test', 'Options User');
        $existing = new AccessPasskeyCredentialEntity($user, 'existing-id', 'existing-handle', 'public-key', ['internal'], 'Laptop');
        $challengeService = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $challengeService->expects(self::once())->method('issue')->with(
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            $user,
        )->willReturn([
            'challenge' => 'registration-challenge',
            'state' => new AccessPasskeyChallengeEntity(
                'registration-challenge',
                AccessPasskeyCeremonyPurpose::Registration,
                'example.test',
                'https://example.test',
                new \DateTimeImmutable(),
                new \DateTimeImmutable('+5 minutes'),
                $user,
            ),
        ]);
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findActiveForUser')->with($user)->willReturn([$existing]);

        $service = new AccessPasskeyRegistrationService(
            $challengeService,
            $this->createMock(AccessPasskeyAttestationVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $repository,
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        $options = $service->issueOptions($user, new AccessPasskeyRelyingPartyConfig('example.test', 'Example', 'https://example.test'))->toArray();

        self::assertSame('registration-challenge', $options['publicKey']['challenge']);
        self::assertSame('example.test', $options['publicKey']['rp']['id']);
        self::assertSame('options@example.test', $options['publicKey']['user']['name']);
        self::assertSame([['type' => 'public-key', 'id' => 'existing-id', 'transports' => ['internal']]], $options['publicKey']['excludeCredentials']);
        self::assertSame([['type' => 'public-key', 'alg' => -7], ['type' => 'public-key', 'alg' => -257]], $options['publicKey']['pubKeyCredParams']);
    }

    public function testCompletesVerifiedRegistrationAndRecordsSecurityEvent(): void
    {
        $user = new AccessEntity('complete@example.test', 'Complete User');
        $config = new AccessPasskeyRelyingPartyConfig('example.test', 'Example', 'https://example.test');
        $challenge = 'registration-challenge';
        $state = new AccessPasskeyChallengeEntity(
            $challenge,
            AccessPasskeyCeremonyPurpose::Registration,
            $config->id,
            $config->origin,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 minutes'),
            $user,
        );
        $challengeService = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $challengeService->expects(self::once())->method('consume')->with(
            $challenge,
            AccessPasskeyCeremonyPurpose::Registration,
            $config->id,
            $config->origin,
        )->willReturn($state);

        $userHandle = rtrim(strtr(base64_encode(hash('sha256', $user->getUserIdentifier(), true)), '+/', '-_'), '=');
        $verifier = $this->createMock(AccessPasskeyAttestationVerifierInterface::class);
        $verifier->expects(self::once())->method('verify')->willReturn(new AccessPasskeyAttestationResult(
            'credential-id',
            $userHandle,
            'public-key',
            ['internal'],
            3,
        ));

        $credential = new AccessPasskeyCredentialEntity($user, 'credential-id', $userHandle, 'public-key', ['internal'], 'Phone', 3);
        $credentialService = $this->createMock(AccessPasskeyCredentialServiceInterface::class);
        $credentialService->expects(self::once())->method('register')->with(
            $user,
            'credential-id',
            $userHandle,
            'public-key',
            ['internal'],
            'Phone',
            3,
        )->willReturn($credential);

        $securityEvents = $this->createMock(AccessSecurityEventServiceInterface::class);
        $securityEvents->expects(self::once())->method('record')->with(
            AccessSecurityEventType::PasskeyRegistered,
            AccessSecurityEventSeverity::Info,
            $user,
            null,
            ['credentialName' => 'Phone'],
        );

        $service = new AccessPasskeyRegistrationService(
            $challengeService,
            $verifier,
            $credentialService,
            $this->createMock(AccessPasskeyCredentialRepositoryInterface::class),
            $securityEvents,
        );

        self::assertSame($credential, $service->complete($user, $config, ['challenge' => $challenge], 'Phone'));
    }

    public function testDefaultVerifierFailsClosedWithStableError(): void
    {
        $this->expectException(AccessPasskeyVerificationUnavailableException::class);
        $this->expectExceptionMessage('Passkey verification is temporarily unavailable.');

        (new AccessUnavailablePasskeyAttestationVerifier())->verify(
            [],
            'challenge',
            new AccessPasskeyRelyingPartyConfig('example.test', 'Example', 'https://example.test'),
            new AccessEntity('unavailable@example.test', 'Unavailable'),
        );
    }
}
