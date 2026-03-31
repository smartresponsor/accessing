<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingSecurityEventController extends AbstractController
{
    #[Route('/security-events', name: 'accessing_security_event_index', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Account|null $account */
        $account = $this->getUser();
        if (!$account instanceof Account) {
            return $this->redirectToRoute('accessing_login');
        }

        $events = $entityManager->createQuery(
            'SELECT securityEvent FROM App\Entity\SecurityEvent securityEvent WHERE securityEvent.account = :account ORDER BY securityEvent.occurredAt DESC'
        )
            ->setParameter('account', $account)
            ->setMaxResults(50)
            ->getResult();

        return $this->render('accessing/security_event/index.html.twig', [
            'events' => $events,
        ]);
    }
}
