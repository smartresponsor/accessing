<?php

declare(strict_types=1);

namespace App\Accessing\Service\OAuth;

use App\Accessing\Dto\AccessExternalIdentityProfile;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\RepositoryInterface\AccessExternalIdentityRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\OAuth\AccessExternalAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessExternalAuthenticationService implements AccessExternalAuthenticationServiceInterface
{
    public function __construct(
        private AccessExternalIdentityRepositoryInterface $externalIdentityRepository,
        private AccessRepositoryInterface $accessRepository,
        private AccessSecurityEventServiceInterface $securityEventService,
    ) {
    }

    public function resolve(AccessExternalIdentityProfile $profile, Request $request): AccessEntity
    {
        $identity = $this->externalIdentityRepository->findOneByProviderAndSubject($profile->provider, $profile->subject);

        if ($identity instanceof AccessExternalIdentityEntity) {
            $identity->recordAuthentication(
                $profile->email,
                $profile->emailVerified,
                $profile->displayName,
                $profile->avatarUrl,
            );
            $this->externalIdentityRepository->save($identity, true);

            return $identity->getUser();
        }

        $existingUser = $this->accessRepository->findOneByEmailAddress($profile->email);
        if ($existingUser instanceof AccessEntity) {
            $this->securityEventService->record(
                AccessSecurityEventType::ExternalIdentityConflict,
                AccessSecurityEventSeverity::Warning,
                $existingUser,
                $request,
                ['provider' => $profile->provider],
            );

            throw new \DomainException('An account with this email already exists. Sign in normally and link Google from account settings.');
        }

        $user = new AccessEntity($profile->email, $profile->displayName);
        if ($profile->emailVerified) {
            $user->markEmailVerified();
        }
        $this->accessRepository->save($user, false);

        $identity = new AccessExternalIdentityEntity(
            $user,
            $profile->provider,
            $profile->subject,
            $profile->email,
            $profile->emailVerified,
            $profile->displayName,
            $profile->avatarUrl,
        );
        $this->externalIdentityRepository->save($identity, true);
        $this->securityEventService->record(
            AccessSecurityEventType::ExternalIdentityLinked,
            AccessSecurityEventSeverity::Info,
            $user,
            $request,
            ['provider' => $profile->provider],
        );

        return $user;
    }
}
