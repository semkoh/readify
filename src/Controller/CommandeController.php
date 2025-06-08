<?php

namespace App\Controller;


use App\Entity\Livre;  // Ajoutez cette ligne
use App\Entity\Contenir; // Ajoutez cette ligne
use App\Entity\Commande;
use App\Service\PayPalService;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\LivreRepository; // en haut si pas encore importé
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commande')]
final class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $user = $this->getUser();
    
        // Redirection sécurité si non connecté (optionnel selon le système global)
        if (!$user) {
            $this->addFlash('danger', 'Veuillez vous connecter pour voir vos commandes.');
            return $this->redirectToRoute('app_login');
        }
    
        if ($this->isGranted('ROLE_ADMIN')) {
            // 👑 Admin : voit toutes les commandes
            $commandes = $commandeRepository->findAll();
        } else {
            // 👤 Utilisateur : voit uniquement ses commandes (toutes, ou uniquement en cours si tu préfères)
            $commandes = $commandeRepository->findBy([
                'utilisateur' => $user,
                //'etat' => 'en cours'
            ]);
            
        }
    
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }
    

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
     public function new(Request $request, EntityManagerInterface $entityManager, LivreRepository $livreRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($commande->getMethodePaiement())) {
                $commande->setMethodePaiement('paypal');
            }

            if ($commande->getEtat() === 'payée') {
                $commande->setDatePaiement(new \DateTime());
            }

            $livres = $form->get('livres')->getData();
            $total = 0;
            foreach ($livres as $livre) {
                $total += $livre->getPrix();
                $contenir = new Contenir();
                $contenir->setCommande($commande);
                $contenir->setLivre($livre);
                $contenir->setQuantite(1);
                $entityManager->persist($contenir);
            }

            $commande->setPrixTotal($total);
            $entityManager->persist($commande);
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index');
        }

      // ✅ On récupère TOUS les livres pour les prix JS
    $livresEntities = $livreRepo->findAll();
    $livresPrix = [];
    foreach ($livresEntities as $livre) {
        $livresPrix[$livre->getId()] = $livre->getPrix();
    }

    return $this->render('commande/new.html.twig', [
        'commande' => $commande,
        'form' => $form->createView(),
        'livresPrix' => $livresPrix, // 🔁 envoyé au template pour JS
    ]);
    }

    


    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $form = $this->createForm(CommandeType::class, $commande);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Paiement
        if (empty($commande->getMethodePaiement())) {
            $commande->setMethodePaiement('paypal');
        }

        if ($commande->getEtat() === 'payée') {
            $commande->setDatePaiement(new \DateTime());
        }

        // 🔽 Supprimer les anciennes relations Contenir
        foreach ($commande->getContenirs() as $contenir) {
            $entityManager->remove($contenir);
        }
        $commande->getContenirs()->clear();

        // 🔽 Récupérer les livres sélectionnés
        $livres = $form->get('livres')->getData();
        foreach ($livres as $livre) {
            $contenir = new Contenir();
            $contenir->setCommande($commande);
            $contenir->setLivre($livre);
            $contenir->setQuantite(1);
            $entityManager->persist($contenir);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_commande_index');
    }

    return $this->render('commande/edit.html.twig', [
        'commande' => $commande,
        'form' => $form->createView(),
    ]);
}




    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
    // Cette méthode affiche les détails d'une commande spécifique
        return $this->render('commande/show.html.twig', [
          'commande' => $commande,
        ]);
    }

    #[Route('/{id}/add-to-cart', name: 'app_livre_add_to_cart', methods: ['POST'])]
public function addToCart(int $id, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    if (!$user) {
        $this->addFlash('danger', 'Vous devez être connecté pour ajouter des livres à votre panier.');
        return $this->redirectToRoute('app_login');
    }

    $livre = $entityManager->getRepository(Livre::class)->find($id);

    if (!$livre) {
        $this->addFlash('danger', 'Le livre n\'a pas été trouvé.');
        return $this->redirectToRoute('app_livre_index');
    }

    // Récupérer la commande en cours de l'utilisateur
    $commande = $entityManager->getRepository(Commande::class)->findOneBy([
        'utilisateur' => $user,
        'etat' => 'en cours',
    ]);

    if (!$commande) {
        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setEtat('en cours');
        $commande->setDateCommande(new \DateTime());
        $entityManager->persist($commande);
    }

    // Vérifier si le livre est déjà dans le panier
    $contenir = $entityManager->getRepository(Contenir::class)->findOneBy([
        'commande' => $commande,
        'livre' => $livre,
    ]);

    if ($contenir) {
        // Si le livre existe déjà dans le panier, on augmente la quantité
        $contenir->setQuantite($contenir->getQuantite() + 1);
    } else {
        // Sinon, on ajoute le livre au panier
        $contenir = new Contenir();
        $contenir->setLivre($livre);
        $contenir->setCommande($commande);
        $contenir->setQuantite(1); // Ajouter le livre avec quantité 1
        $entityManager->persist($contenir);
    }

    // Mise à jour du prix total de la commande
    $commande->setPrixTotal($commande->getPrixTotal() + $livre->getPrix());
    $entityManager->flush();

    $this->addFlash('success', 'Livre ajouté au panier!');
    return $this->redirectToRoute('app_panier_index');
}

    

//moi
#[Route('/{id}/payer', name: 'app_commande_payer', methods: ['GET'])]
public function payer(Commande $commande, EntityManagerInterface $entityManager, PayPalService $paypalService): Response
{
    // Vérifier si la commande est en cours
    if ($commande->getEtat() !== 'en cours') {
        $this->addFlash('error', 'La commande n\'est pas en cours.');
        return $this->redirectToRoute('app_commande_index');
    }

    // Créer le paiement PayPal
    $payment = $paypalService->createPayment($commande->getPrixTotal(), 'USD', $commande->getId());

    if ($payment) {
        // Rediriger vers PayPal pour le paiement
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                return $this->redirect($link->getHref());  // Redirection vers PayPal
            }
        }
    }

    // Si le paiement n'a pas pu être créé, afficher une erreur
    $this->addFlash('error', 'Une erreur est survenue lors de la création du paiement.');
    return $this->redirectToRoute('app_commande_index');
}

#[Route('/{id}/delete', name: 'app_commande_delete', methods: ['POST'])]
public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete' . $commande->getId(), $request->request->get('_token'))) {
        $entityManager->remove($commande);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_commande_index');
}


}
