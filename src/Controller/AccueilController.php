<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\LivreRepository;

class AccueilController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(LivreRepository $livreRepository): Response
    {
        $livres = $livreRepository->findBy([], null, 6); // les 6 derniers livres

        return $this->render('accueil/index.html.twig', [
            'livres' => $livres,
        ]);
    }
}

