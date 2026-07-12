<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\Dto\AccessPasskeyAttestationResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessPasskeyVerificationException;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAttestationVerifierInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final readonly class AccessWebauthnAttestationVerifier implements AccessPasskeyAttestationVerifierInterface
{
    public function __construct(private AccessWebauthnCredentialRecordCodec $codec)
    {
    }

    public function verify(array $credentialResponse, string $expectedChallenge, AccessPasskeyRelyingPartyConfig $relyingParty, AccessEntity $user): AccessPasskeyAttestationResult
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

            return new AccessPasskeyAttestationResult(
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

    private static function host(AccessPasskeyRelyingPartyConfig $relyingParty): string
    {
        $host = parse_url($relyingParty->origin, PHP_URL_HOST);

        return is_string($host) && '' !== $host ? $host : $relyingParty->id;
    }

    private static function userHandle(AccessEntity $user): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $user->getUserIdentifier(), true)), '+/', '-_'), '=');
    }
}
