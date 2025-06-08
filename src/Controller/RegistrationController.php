<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Enum\UtilisateurRole;
use App\Form\RegistrationFormType;
use App\Security\LoginAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
    
            // Encode le mot de passe
            $user->setMotDePasse(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );
    
            // Définir un rôle par défaut pour l'utilisateur (si aucun rôle choisi)
            if (!$user->getRole()) {
                $user->setRole(UtilisateurRole::UTILISATEUR);  // Assigner "utilisateur" par défaut
            }
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Connexion automatique après inscription
            return $security->login($user, LoginAuthenticator::class, 'main');
        }
    
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
    
}
