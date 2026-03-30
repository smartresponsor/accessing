<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AccessingAccountController extends AbstractController
{
    #[Route('/', name: 'accessing_home', methods: ['GET'])]
    public function home(): Response
    {
        /** @var Account|null $account */
        $account = $this->getUser();

        return $this->render('accessing/account/home.html.twig', [
            'account' => $account,
        ]);
    }

    #[Route('/login', name: 'accessing_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof Account) {
            return $this->redirectToRoute('accessing_dashboard');
        }

        return $this->render('accessing/account/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'accessing_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Logout is handled by the firewall.');
    }

    #[Route('/dashboard', name: 'accessing_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        /** @var Account|null $account */
        $account = $this->getUser();

        return $this->render('accessing/account/dashboard.html.twig', [
            'account' => $account,
        ]);
    }
}
