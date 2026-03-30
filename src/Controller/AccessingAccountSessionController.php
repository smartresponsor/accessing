<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingAccountSessionController extends AbstractController
{
    #[Route('/sessions', name: 'accessing_account_session_index', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sessions = $entityManager->createQuery(
            'SELECT accountSession FROM App\\Entity\\AccountSession accountSession ORDER BY accountSession.lastSeenAt DESC'
        )
            ->setMaxResults(50)
            ->getResult();

        return $this->render('accessing/account/session_index.html.twig', [
            'sessions' => $sessions,
        ]);
    }
}
