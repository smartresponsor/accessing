<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AccountRegistrationRequest;
use App\Form\AccountRegistrationFormType;
use App\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingRegistrationController extends AbstractController
{
    #[Route('/register', name: 'accessing_register', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        AccessingAccountRegistrationServiceInterface $registrationService,
    ): Response {
        $registrationRequest = new AccountRegistrationRequest();
        $form = $this->createForm(AccountRegistrationFormType::class, $registrationRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $account = $registrationService->register($registrationRequest);

            $this->addFlash('success', sprintf('Account %s was created. Verification flow is the next implementation step.', $account->getEmail()));

            return $this->redirectToRoute('accessing_login');
        }

        return $this->render('accessing/account/register.html.twig', [
            'form' => $form,
        ]);
    }
}
