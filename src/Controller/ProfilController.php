<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil')]
class ProfilController extends AbstractController
{
    #[Route('/', name: 'app_profil')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_UTILISATEUR');

        return $this->render('profil/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
