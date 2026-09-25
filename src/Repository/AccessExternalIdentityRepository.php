<?php

declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\RepositoryInterface\AccessExternalIdentityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AccessExternalIdentityEntity> */
final class AccessExternalIdentityRepository extends ServiceEntityRepository implements AccessExternalIdentityRepositoryInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessExternalIdentityEntity::class);
    }

    /**
     * Executes the find one by provider and subject operation within the canonical Accessing component workflow.
     */
    public function findOneByProviderAndSubject(string $provider, string $subject): ?AccessExternalIdentityEntity
    {
        $identity = $this->createQueryBuilder('identity')
            ->addSelect('user')
            ->join('identity.user', 'user')
            ->andWhere('identity.objectSource.provider = :provider')
            ->andWhere('identity.objectSource.externalId = :subject')
            ->setParameter('provider', mb_strtolower(trim($provider)))
            ->setParameter('subject', trim($subject))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $identity instanceof AccessExternalIdentityEntity ? $identity : null;
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessExternalIdentityEntity $identity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($identity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
