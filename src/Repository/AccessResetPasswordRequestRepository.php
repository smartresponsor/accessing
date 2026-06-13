<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessResetPasswordRequestEntity;
use App\Accessing\RepositoryInterface\AccessResetPasswordRequestRepositoryInterface as AccessResetPasswordRepositoryContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * @extends ServiceEntityRepository<AccessResetPasswordRequestEntity>
 */
final class AccessResetPasswordRequestRepository extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface, AccessResetPasswordRepositoryContract
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessResetPasswordRequestEntity::class);
    }

    public function save(AccessResetPasswordRequestEntity $resetPasswordRequest, bool $flush = false): void
    {
        $this->getEntityManager()->persist($resetPasswordRequest);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        return new AccessResetPasswordRequestEntity($user instanceof AccessEntity ? $user : throw new \InvalidArgumentException('Expected AccessEntity user.'), $expiresAt, $selector, $hashedToken);
    }
}
