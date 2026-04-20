<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected function refreshDatabase(): EntityManagerInterface
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);

        if ([] !== $metadata) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }

        return $entityManager;
    }

    protected function tearDown(): void
    {
        $container = static::getContainer();

        if ($container->has(EntityManagerInterface::class)) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $container->get(EntityManagerInterface::class);
            $entityManager->clear();
            $entityManager->close();
        }

        self::ensureKernelShutdown();

        parent::tearDown();
    }
}
