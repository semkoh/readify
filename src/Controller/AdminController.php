<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        // Vérifie que l'utilisateur est un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupère l'utilisateur connecté
        $user = $this->getUser();

        // Rendre la vue avec les informations de l'utilisateur
        return $this->render('admin/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profil', name: 'app_profil')]
    public function profil(): Response
    {
        // Vérifie que l'utilisateur est un admin
        if ($this->isGranted('ROLE_ADMIN')) {
            // Redirige l'admin vers /admin
            return $this->redirectToRoute('app_admin');
        }

        // Si ce n'est pas un admin, montrer le profil normal
        $user = $this->getUser();
        
        return $this->render('profil/index.html.twig', [
            'user' => $user,
        ]);
    }
}
