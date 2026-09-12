<?php

declare(strict_types=1);

namespace App\Accessing\Verifier\Passkey;

use App\Accessing\Codec\Passkey\AccessWebauthnCredentialRecordCodec;
use App\Accessing\DTO\AccessPasskeyAssertionResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Exception\AccessPasskeyVerificationException;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAssertionVerifierInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Defines the webauthn assertion verifier type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessWebauthnAssertionVerifier implements AccessPasskeyAssertionVerifierInterface
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
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
        string $storedPublicKey,
        string $storedUserHandle,
        ?string $storedCredentialRecord = null,
    ): AccessPasskeyAssertionResultDTO {
        if (null === $storedCredentialRecord) {
            throw new AccessPasskeyVerificationUnavailableException();
        }

        try {
            $publicKeyCredential = $this->codec->decodePublicKeyCredential($credentialResponse);
            if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
                throw new \UnexpectedValueException('Unexpected authenticator response type.');
            }

            $record = $this->codec->decode($storedCredentialRecord);
            $factory = new CeremonyStepManagerFactory();
            $factory->setAllowedOrigins([$relyingParty->origin]);
            $validator = AuthenticatorAssertionResponseValidator::create($factory->requestCeremony());
            $options = PublicKeyCredentialRequestOptions::create(
                Base64UrlSafe::decodeNoPadding($expectedChallenge),
                $relyingParty->id,
                [$record->getPublicKeyCredentialDescriptor()],
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            );
            $updatedRecord = $validator->check(
                $record,
                $publicKeyCredential->response,
                $options,
                self::host($relyingParty),
                Base64UrlSafe::decodeNoPadding($storedUserHandle),
            );

            return new AccessPasskeyAssertionResultDTO(
                Base64UrlSafe::encodeUnpadded($updatedRecord->publicKeyCredentialId),
                Base64UrlSafe::encodeUnpadded($updatedRecord->userHandle),
                $updatedRecord->counter,
                $this->codec->encode($updatedRecord),
            );
        } catch (AccessPasskeyVerificationUnavailableException $exception) {
            throw $exception;
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
}
