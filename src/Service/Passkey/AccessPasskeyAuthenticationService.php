<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\DTO\AccessPasskeyAuthenticationOptionsDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAssertionVerifierInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the passkey authentication service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessPasskeyAuthenticationService implements AccessPasskeyAuthenticationServiceInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessPasskeyChallengeServiceInterface $challengeService,
        private AccessPasskeyAssertionVerifierInterface $assertionVerifier,
        private AccessPasskeyCredentialServiceInterface $credentialService,
        private AccessPasskeyCredentialRepositoryInterface $credentialRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
    ) {
    }

    /**
     * Executes the issue options operation within the canonical Accessing component workflow.
     */
    public function issueOptions(AccessPasskeyRelyingPartyConfigDTO $relyingParty, ?AccessEntity $user = null): AccessPasskeyAuthenticationOptionsDTO
    {
        $issued = $this->challengeService->issue(AccessPasskeyCeremonyPurpose::Authentication, $relyingParty->id, $relyingParty->origin, $user);
        $credentials = $user instanceof AccessEntity ? $this->credentialRepository->findActiveForUser($user) : [];

        return new AccessPasskeyAuthenticationOptionsDTO(
            $issued['challenge'],
            $relyingParty->id,
            array_map(static fn (AccessPasskeyCredentialEntity $credential): array => [
                'type' => 'public-key',
                'id' => $credential->getCredentialId(),
                'transports' => $credential->getTransports(),
            ], $credentials),
        );
    }

    /**
     * Executes the complete operation within the canonical Accessing component workflow.
     */
    public function complete(AccessPasskeyRelyingPartyConfigDTO $relyingParty, array $credentialResponse, ?Request $request = null): AccessEntity
    {
        $challenge = $credentialResponse['challenge'] ?? null;
        $credentialId = $credentialResponse['credentialId'] ?? null;
        if (!is_string($challenge) || '' === $challenge || !is_string($credentialId) || '' === $credentialId) {
            throw new \DomainException('Passkey authentication response is incomplete.');
        }

        $state = $this->challengeService->consume($challenge, AccessPasskeyCeremonyPurpose::Authentication, $relyingParty->id, $relyingParty->origin);
        $credential = $this->credentialRepository->findOneByCredentialId($credentialId);
        if (!$credential instanceof AccessPasskeyCredentialEntity || !$credential->isActive()) {
            throw new \DomainException('Passkey credential is unavailable.');
        }
        if ($state->getUser() instanceof AccessEntity && $state->getUser() !== $credential->getUser()) {
            throw new \DomainException('Passkey authentication challenge belongs to a different user.');
        }

        $verified = $this->assertionVerifier->verify($credentialResponse, $challenge, $relyingParty, $credential->getPublicKey(), $credential->getUserHandle(), $credential->getCredentialRecord());
        if (!hash_equals($credential->getCredentialId(), $verified->credentialId) || !hash_equals($credential->getUserHandle(), $verified->userHandle)) {
            throw new \DomainException('Verified passkey assertion does not match the stored credential.');
        }

        try {
            $this->credentialService->recordSuccessfulAssertion($credentialId, $verified->signCount, $verified->credentialRecord);
        } catch (\DomainException $exception) {
            $this->securityEventService->record(AccessSecurityEventType::PasskeyCounterRegression, AccessSecurityEventSeverity::Warning, $credential->getUser(), $request, ['credentialName' => $credential->getName()]);
            throw $exception;
        }

        $this->securityEventService->record(AccessSecurityEventType::PasskeyAuthenticated, AccessSecurityEventSeverity::Info, $credential->getUser(), $request, ['credentialName' => $credential->getName()]);

        return $credential->getUser();
    }
}
