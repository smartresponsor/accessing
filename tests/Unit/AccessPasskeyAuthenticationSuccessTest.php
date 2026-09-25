<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\DTO\AccessPasskeyAssertionResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\Service\Passkey\AccessPasskeyAuthenticationService;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAssertionVerifierInterface;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyAuthenticationSuccessTest extends TestCase
{
    public function testIssuesBoundAndUsernamelessAuthenticationOptions(): void
    {
        $user = new AccessEntity('options-auth@example.test', 'Options Auth');
        $credential = new AccessPasskeyCredentialEntity($user, 'credential-id', 'handle', 'key', ['internal'], 'Laptop');
        $config = new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test');
        $challenges = $this->createMock(AccessPasskeyChallengeServiceInterface::class);
        $challenges->expects(self::exactly(2))->method('issue')->willReturnOnConsecutiveCalls(
            ['challenge' => 'bound-challenge', 'state' => new AccessPasskeyChallengeEntity(
                'bound-challenge',
                AccessPasskeyCeremonyPurpose::Authentication,
                $config->id,
                $config->origin,
                new \DateTimeImmutable(),
                new \DateTimeImmutable('+5 minutes'),
                $user,
            )],
            ['challenge' => 'anonymous-challenge', 'state' => new AccessPasskeyChallengeEntity(
                'anonymous-challenge',
                AccessPasskeyCeremonyPurpose::Authentication,
                $config->id,
                $config->origin,
                new \DateTimeImmutable(),
                new \DateTimeImmutable('+5 minutes'),
            )],
        );
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->expects(self::once())->method('findActiveForUser')->with($user)->willReturn([$credential]);
        $service = new AccessPasskeyAuthenticationService(
            $challenges,
            $this->createMock(AccessPasskeyAssertionVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $repository,
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        $bound = $service->issueOptions($config, $user)->toArray();
        self::assertSame('bound-challenge', $bound['publicKey']['challenge']);
        self::assertCount(1, $bound['publicKey']['allowCredentials']);

        $anonymous = $service->issueOptions($config)->toArray();
        self::assertSame('anonymous-challenge', $anonymous['publicKey']['challenge']);
        self::assertSame([], $anonymous['publicKey']['allowCredentials']);
    }

    public function testAuthenticationRejectsIncompleteUnavailableAndMismatchedVerifiedAssertion(): void
    {
        $user = new AccessEntity('auth-branches@example.test');
        $config = new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test');
        $service = new AccessPasskeyAuthenticationService(
            $this->createMock(AccessPasskeyChallengeServiceInterface::class),
            $this->createMock(AccessPasskeyAssertionVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $this->createMock(AccessPasskeyCredentialRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        try {
            $service->complete($config, []);
            self::fail('Incomplete passkey assertion must be rejected.');
        } catch (\DomainException $exception) {
            self::assertSame('Passkey authentication response is incomplete.', $exception->getMessage());
        }

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
        $missingRepository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $missingRepository->method('findOneByCredentialId')->willReturn(null);
        $missingService = new AccessPasskeyAuthenticationService(
            $challenges,
            $this->createMock(AccessPasskeyAssertionVerifierInterface::class),
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $missingRepository,
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        try {
            $missingService->complete($config, ['challenge' => 'challenge', 'credentialId' => 'missing']);
            self::fail('Missing passkey credential must be rejected.');
        } catch (\DomainException $exception) {
            self::assertSame('Passkey credential is unavailable.', $exception->getMessage());
        }

        $credential = new AccessPasskeyCredentialEntity($user, 'credential-id', 'handle', 'key', [], 'Key');
        $repository = $this->createMock(AccessPasskeyCredentialRepositoryInterface::class);
        $repository->method('findOneByCredentialId')->willReturn($credential);
        $verifier = $this->createMock(AccessPasskeyAssertionVerifierInterface::class);
        $verifier->method('verify')->willReturn(new AccessPasskeyAssertionResultDTO('wrong-id', 'wrong-handle', 1));
        $mismatchService = new AccessPasskeyAuthenticationService(
            $challenges,
            $verifier,
            $this->createMock(AccessPasskeyCredentialServiceInterface::class),
            $repository,
            $this->createMock(AccessSecurityEventServiceInterface::class),
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Verified passkey assertion does not match the stored credential.');
        $mismatchService->complete($config, ['challenge' => 'challenge', 'credentialId' => 'credential-id']);
    }

    public function testCompletesVerifiedAssertionAndRecordsSuccess(): void
    {
        $user = new AccessEntity('auth@example.test', 'Auth');
        $credential = new AccessPasskeyCredentialEntity($user, 'credential-id', 'handle', 'key', ['internal'], 'Laptop', 2);
        $config = new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'https://example.test');
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
        $verifier->method('verify')->willReturn(new AccessPasskeyAssertionResultDTO('credential-id', 'handle', 3));
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
