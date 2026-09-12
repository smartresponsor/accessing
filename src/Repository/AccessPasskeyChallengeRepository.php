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
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessPasskeyChallengeEntity::class);
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessPasskeyChallengeEntity $challenge, bool $flush = false): void
    {
        $this->getEntityManager()->persist($challenge);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the find one by challenge hash operation within the canonical Accessing component workflow.
     */
    public function findOneByChallengeHash(string $challengeHash): ?AccessPasskeyChallengeEntity
    {
        $challenge = $this->findOneBy(['challengeHash' => $challengeHash]);

        return $challenge instanceof AccessPasskeyChallengeEntity ? $challenge : null;
    }
}
