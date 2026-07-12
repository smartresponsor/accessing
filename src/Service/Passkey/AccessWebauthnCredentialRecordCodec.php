<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;

final readonly class AccessWebauthnCredentialRecordCodec
{
    private SerializerInterface $serializer;

    public function __construct()
    {
        $manager = AttestationStatementSupportManager::create();
        $manager->add(NoneAttestationStatementSupport::create());
        $this->serializer = (new WebauthnSerializerFactory($manager))->create();
    }

    public function encode(CredentialRecord $credentialRecord): string
    {
        return $this->serializer->serialize($credentialRecord, 'json');
    }

    public function decode(string $serializedCredentialRecord): CredentialRecord
    {
        if ('' === trim($serializedCredentialRecord)) {
            throw new \InvalidArgumentException('Serialized passkey credential record cannot be empty.');
        }

        return $this->serializer->deserialize($serializedCredentialRecord, CredentialRecord::class, 'json');
    }

    /** @param array<string, mixed> $credentialResponse */
    public function decodePublicKeyCredential(array $credentialResponse): PublicKeyCredential
    {
        return $this->serializer->deserialize(json_encode($credentialResponse, JSON_THROW_ON_ERROR), PublicKeyCredential::class, 'json');
    }
}
