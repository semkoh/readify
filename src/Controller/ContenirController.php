<?php

namespace App\Controller;
use App\Entity\Commande;
use App\Entity\Livre;
use App\Entity\Contenir;
use App\Form\ContenirType;
use App\Repository\ContenirRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contenir')]
final class ContenirController extends AbstractController
{
    #[Route(name: 'app_contenir_index', methods: ['GET'])]
    public function index(ContenirRepository $contenirRepository): Response
    {
        return $this->render('contenir/index.html.twig', [
            'contenirs' => $contenirRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_contenir_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contenir = new Contenir();
        $form = $this->createForm(ContenirType::class, $contenir);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contenir);
            $entityManager->flush();

            return $this->redirectToRoute('app_contenir_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contenir/new.html.twig', [
            'contenir' => $contenir,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contenir_show', methods: ['GET'])]
    public function show(Contenir $contenir): Response
    {
        return $this->render('contenir/show.html.twig', [
            'contenir' => $contenir,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contenir_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contenir $contenir, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContenirType::class, $contenir);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_contenir_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contenir/edit.html.twig', [
            'contenir' => $contenir,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contenir_delete', methods: ['POST'])]
public function delete(Request $request, Contenir $contenir, EntityManagerInterface $entityManager): Response
{
    // Vérification si la commande associée existe
    $commande = $contenir->getCommande();
    if (!$commande) {
        $this->addFlash('error', 'La commande associée à cet élément n\'existe plus.');
        return $this->redirectToRoute('app_panier_index');
    }

    // Suppression du contenir
    if ($this->isCsrfTokenValid('delete'.$contenir->getId(), $request->request->get('_token'))) {
        $entityManager->remove($contenir);
        $entityManager->flush();
    }

    $this->addFlash('success', 'Élément supprimé du panier.');
    return $this->redirectToRoute('app_panier_index');
}



//moi
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

#[Route('/{id}/remove', name: 'app_contenir_remove', methods: ['POST'])]
public function removeFromCart(int $id, EntityManagerInterface $entityManager): Response
{
    $item = $entityManager->getRepository(Contenir::class)->find($id);

    if (!$item) {
        $this->addFlash('error', 'L\'élément n\'existe pas.');
        return $this->redirectToRoute('app_panier_index');
    }

    // Vérifier si la commande existe toujours
    $commande = $item->getCommande();
    if (!$commande) {
        $this->addFlash('error', 'La commande associée à cet élément n\'existe plus.');
        return $this->redirectToRoute('app_panier_index');
    }

    // Mise à jour du prix total de la commande avant suppression de l'élément
    $commande->setPrixTotal($commande->getPrixTotal() - ($item->getQuantite() * $item->getLivre()->getPrix()));

    // Supprimer l'élément du panier
    $entityManager->remove($item);
    $entityManager->flush();

    $this->addFlash('success', 'Élément supprimé du panier.');
    return $this->redirectToRoute('app_panier_index');
}



    
}
