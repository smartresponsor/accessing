<?php

declare(strict_types=1);

namespace App\Accessing\Verifier\Passkey;

use App\Accessing\Codec\Passkey\AccessWebauthnCredentialRecordCodec;
use App\Accessing\DTO\AccessPasskeyAttestationResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessPasskeyVerificationException;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAttestationVerifierInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Defines the webauthn attestation verifier type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessWebauthnAttestationVerifier implements AccessPasskeyAttestationVerifierInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(private AccessWebauthnCredentialRecordCodec $codec)
    {
    }

    /**
     * Executes the verify operation within the canonical Accessing component workflow.
     */
    public function verify(array $credentialResponse, string $expectedChallenge, AccessPasskeyRelyingPartyConfigDTO $relyingParty, AccessEntity $user): AccessPasskeyAttestationResultDTO
    {
        try {
            $publicKeyCredential = $this->codec->decodePublicKeyCredential($credentialResponse);
            if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
                throw new \UnexpectedValueException('Unexpected authenticator response type.');
            }

            $factory = new CeremonyStepManagerFactory();
            $factory->setAllowedOrigins([$relyingParty->origin]);
            $validator = AuthenticatorAttestationResponseValidator::create($factory->creationCeremony());
            $userHandle = Base64UrlSafe::decodeNoPadding(self::userHandle($user));
            $options = PublicKeyCredentialCreationOptions::create(
                PublicKeyCredentialRpEntity::create($relyingParty->name, $relyingParty->id),
                PublicKeyCredentialUserEntity::create($user->getUserIdentifier(), $userHandle, $user->getDisplayName() ?? $user->getUserIdentifier()),
                Base64UrlSafe::decodeNoPadding($expectedChallenge),
                [PublicKeyCredentialParameters::createPk(-7), PublicKeyCredentialParameters::createPk(-257)],
                attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            );
            $record = $validator->check($publicKeyCredential->response, $options, self::host($relyingParty));

            return new AccessPasskeyAttestationResultDTO(
                Base64UrlSafe::encodeUnpadded($record->publicKeyCredentialId),
                Base64UrlSafe::encodeUnpadded($record->userHandle),
                Base64UrlSafe::encodeUnpadded($record->credentialPublicKey),
                array_values($record->transports),
                $record->counter,
                $this->codec->encode($record),
            );
        } catch (\Throwable) {
            throw new AccessPasskeyVerificationException();
        }
    }

    /**
     * Executes the host operation within the canonical Accessing component workflow.
     */
    private static function host(AccessPasskeyRelyingPartyConfigDTO $relyingParty): string
    {
        $host = parse_url($relyingParty->origin, PHP_URL_HOST);

        return is_string($host) && '' !== $host ? $host : $relyingParty->id;
    }

    /**
     * Executes the user handle operation within the canonical Accessing component workflow.
     */
    private static function userHandle(AccessEntity $user): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $user->getUserIdentifier(), true)), '+/', '-_'), '=');
    }
}
