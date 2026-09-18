<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DTO\AccessPasskeyAttestationResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\Service\Passkey\AccessPasskeyRegistrationService;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\Verifier\Passkey\AccessUnavailablePasskeyAttestationVerifier;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAttestationVerifierInterface;
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

        $options = $service->issueOptions($user, new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test'))->toArray();

        self::assertSame('registration-challenge', $options['publicKey']['challenge']);
        self::assertSame('example.test', $options['publicKey']['rp']['id']);
        self::assertSame('options@example.test', $options['publicKey']['user']['name']);
        self::assertSame([['type' => 'public-key', 'id' => 'existing-id', 'transports' => ['internal']]], $options['publicKey']['excludeCredentials']);
        self::assertSame([['type' => 'public-key', 'alg' => -7], ['type' => 'public-key', 'alg' => -257]], $options['publicKey']['pubKeyCredParams']);
    }

    public function testCompletesVerifiedRegistrationAndRecordsSecurityEvent(): void
    {
        $user = new AccessEntity('complete@example.test', 'Complete User');
        $config = new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test');
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
        $verifier->expects(self::once())->method('verify')->willReturn(new AccessPasskeyAttestationResultDTO(
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

    public function testRegistrationRejectsMissingChallengeForeignUserAndMismatchedHandle(): void
    {
        $user = new AccessEntity('registration-branches@example.test');
        $config = new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test');
        $service = new AccessPasskeyRegistrationService(
            $this->createMock(AccessPasskeyChallengeServiceInterface::class),
            $this->createMock(AccessPasskeyAttestationVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $this->createMock(AccessPasskeyCredentialRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        foreach ([[], ['challenge' => '']] as $payload) {
            try {
                $service->complete($user, $config, $payload, 'Phone');
                self::fail('Missing or empty registration challenge must be rejected.');
            } catch (\DomainException $exception) {
                self::assertSame('Passkey registration response is missing its challenge.', $exception->getMessage());
            }
        }

        $foreign = new AccessEntity('foreign-registration@example.test');
        $state = new AccessPasskeyChallengeEntity(
            'challenge',
            AccessPasskeyCeremonyPurpose::Registration,
            $config->id,
            $config->origin,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 minutes'),
            $foreign,
        );
        $foreignChallenges = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $foreignChallenges->method('consume')->willReturn($state);
        $foreignService = new AccessPasskeyRegistrationService(
            $foreignChallenges,
            $this->createMock(AccessPasskeyAttestationVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $this->createMock(AccessPasskeyCredentialRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        try {
            $foreignService->complete($user, $config, ['challenge' => 'challenge'], 'Phone');
            self::fail('Foreign registration challenge must be rejected.');
        } catch (\DomainException $exception) {
            self::assertSame('Passkey registration challenge belongs to a different user.', $exception->getMessage());
        }

        $ownState = new AccessPasskeyChallengeEntity(
            'challenge-2',
            AccessPasskeyCeremonyPurpose::Registration,
            $config->id,
            $config->origin,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 minutes'),
            $user,
        );
        $ownChallenges = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $ownChallenges->method('consume')->willReturn($ownState);
        $verifier = $this->createMock(AccessPasskeyAttestationVerifierInterface::class);
        $verifier->method('verify')->willReturn(new AccessPasskeyAttestationResultDTO(
            'credential-id',
            'wrong-user-handle',
            'public-key',
            [],
            0,
        ));
        $mismatchService = new AccessPasskeyRegistrationService(
            $ownChallenges,
            $verifier,
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $this->createMock(AccessPasskeyCredentialRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Verified passkey user handle does not match the registering user.');
        $mismatchService->complete($user, $config, ['challenge' => 'challenge-2'], 'Phone');
    }

    public function testRegistrationOptionsFallBackToIdentifierWhenDisplayNameIsMissing(): void
    {
        $user = new AccessEntity('fallback-options@example.test');
        $challengeService = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $challengeService->method('issue')->willReturn([
            'challenge' => 'challenge',
            'state' => new AccessPasskeyChallengeEntity(
                'challenge',
                AccessPasskeyCeremonyPurpose::Registration,
                'example.test',
                'https://example.test',
                new \DateTimeImmutable(),
                new \DateTimeImmutable('+5 minutes'),
                $user,
            ),
        ]);
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findActiveForUser')->willReturn([]);
        $service = new AccessPasskeyRegistrationService(
            $challengeService,
            $this->createMock(AccessPasskeyAttestationVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $repository,
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        $payload = $service->issueOptions($user, new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test'))->toArray();
        self::assertSame('fallback-options@example.test', $payload['publicKey']['user']['displayName']);
    }

    public function testDefaultVerifierFailsClosedWithStableError(): void
    {
        $this->expectException(AccessPasskeyVerificationUnavailableException::class);
        $this->expectExceptionMessage('Passkey verification is temporarily unavailable.');

        (new AccessUnavailablePasskeyAttestationVerifier())->verify(
            [],
            'challenge',
            new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test'),
            new AccessEntity('unavailable@example.test', 'Unavailable'),
        );
    }
}
