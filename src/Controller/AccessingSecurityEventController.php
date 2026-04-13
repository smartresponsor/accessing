<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventViewProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingSecurityEventController extends AbstractController
{
    #[Route('/security-events', name: 'accessing_security_event_index', methods: ['GET'])]
    public function __invoke(AccessingSecurityEventViewProviderInterface $securityEventViewProvider): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Account|null $account */
        $account = $this->getUser();
        if (!$account instanceof Account) {
            return $this->redirectToRoute('accessing_login');
        }

        $events = $securityEventViewProvider->listRecentForAccount($account, 50);

        return $this->render('accessing/security_event/index.html.twig', [
            'events' => $events,
        ]);
    }
}
