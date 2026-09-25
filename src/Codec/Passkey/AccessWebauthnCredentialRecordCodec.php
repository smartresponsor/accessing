<?php

declare(strict_types=1);

namespace App\Accessing\Codec\Passkey;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;

/**
 * Defines the webauthn credential record codec type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessWebauthnCredentialRecordCodec
{
    private SerializerInterface $serializer;

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct()
    {
        $manager = AttestationStatementSupportManager::create();
        $manager->add(NoneAttestationStatementSupport::create());
        $this->serializer = (new WebauthnSerializerFactory($manager))->create();
    }

    /**
     * Executes the encode operation within the canonical Accessing component workflow.
     */
    public function encode(CredentialRecord $credentialRecord): string
    {
        return $this->serializer->serialize($credentialRecord, 'json');
    }

    /**
     * Executes the decode operation within the canonical Accessing component workflow.
     */
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
