<?php

declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyChallengeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AccessPasskeyChallengeEntity> */
final class AccessPasskeyChallengeRepository extends ServiceEntityRepository implements AccessPasskeyChallengeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessPasskeyChallengeEntity::class);
    }

    public function save(AccessPasskeyChallengeEntity $challenge, bool $flush = false): void
    {
        $this->getEntityManager()->persist($challenge);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByChallengeHash(string $challengeHash): ?AccessPasskeyChallengeEntity
    {
        $challenge = $this->findOneBy(['challengeHash' => $challengeHash]);

        return $challenge instanceof AccessPasskeyChallengeEntity ? $challenge : null;
    }
}
