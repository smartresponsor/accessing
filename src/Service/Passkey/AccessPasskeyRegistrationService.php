<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\DTO\AccessPasskeyRegistrationOptionsDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyCredentialServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAttestationVerifierInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the passkey registration service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessPasskeyRegistrationService implements AccessPasskeyRegistrationServiceInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessPasskeyChallengeServiceInterface $challengeService,
        private AccessPasskeyAttestationVerifierInterface $attestationVerifier,
        private AccessPasskeyCredentialServiceInterface $credentialService,
        private AccessPasskeyCredentialRepositoryInterface $credentialRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
    ) {
    }

    /**
     * Executes the issue options operation within the canonical Accessing component workflow.
     */
    public function issueOptions(
        AccessEntity $user,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
    ): AccessPasskeyRegistrationOptionsDTO {
        $issued = $this->challengeService->issue(
            AccessPasskeyCeremonyPurpose::Registration,
            $relyingParty->id,
            $relyingParty->origin,
            $user,
        );

        $excludeCredentials = array_map(
            static fn (AccessPasskeyCredentialEntity $credential): array => [
                'type' => 'public-key',
                'id' => $credential->getCredentialId(),
                'transports' => $credential->getTransports(),
            ],
            $this->credentialRepository->findActiveForUser($user),
        );

        return new AccessPasskeyRegistrationOptionsDTO(
            $issued['challenge'],
            ['id' => $relyingParty->id, 'name' => $relyingParty->name],
            [
                'id' => self::userHandle($user),
                'name' => $user->getUserIdentifier(),
                'displayName' => $user->getDisplayName() ?? $user->getUserIdentifier(),
            ],
            [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            $excludeCredentials,
            300000,
        );
    }

    /**
     * Executes the complete operation within the canonical Accessing component workflow.
     */
    public function complete(
        AccessEntity $user,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
        array $credentialResponse,
        string $name,
        ?Request $request = null,
    ): AccessPasskeyCredentialEntity {
        $challenge = $credentialResponse['challenge'] ?? null;
        if (!is_string($challenge) || '' === $challenge) {
            throw new \DomainException('Passkey registration response is missing its challenge.');
        }

        $state = $this->challengeService->consume(
            $challenge,
            AccessPasskeyCeremonyPurpose::Registration,
            $relyingParty->id,
            $relyingParty->origin,
        );
        if ($state->getUser() !== $user) {
            throw new \DomainException('Passkey registration challenge belongs to a different user.');
        }

        $verified = $this->attestationVerifier->verify($credentialResponse, $challenge, $relyingParty, $user);
        if (!hash_equals(self::userHandle($user), $verified->userHandle)) {
            throw new \DomainException('Verified passkey user handle does not match the registering user.');
        }

        $credential = $this->credentialService->register(
            $user,
            $verified->credentialId,
            $verified->userHandle,
            $verified->publicKey,
            $verified->transports,
            $name,
            $verified->signCount,
            $verified->credentialRecord,
        );

        $this->securityEventService->record(
            AccessSecurityEventType::PasskeyRegistered,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['credentialName' => $credential->getName()],
        );

        return $credential;
    }

    /**
     * Executes the user handle operation within the canonical Accessing component workflow.
     */
    private static function userHandle(AccessEntity $user): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $user->getUserIdentifier(), true)), '+/', '-_'), '=');
    }
}
