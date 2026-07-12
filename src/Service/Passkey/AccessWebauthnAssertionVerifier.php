<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\Dto\AccessPasskeyAssertionResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Exception\AccessPasskeyVerificationException;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAssertionVerifierInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialRequestOptions;

final readonly class AccessWebauthnAssertionVerifier implements AccessPasskeyAssertionVerifierInterface
{
    public function __construct(private AccessWebauthnCredentialRecordCodec $codec)
    {
    }

    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfig $relyingParty,
        string $storedPublicKey,
        string $storedUserHandle,
        ?string $storedCredentialRecord = null,
    ): AccessPasskeyAssertionResult {
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

            return new AccessPasskeyAssertionResult(
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

    private static function host(AccessPasskeyRelyingPartyConfig $relyingParty): string
    {
        $host = parse_url($relyingParty->origin, PHP_URL_HOST);

        return is_string($host) && '' !== $host ? $host : $relyingParty->id;
    }
}
