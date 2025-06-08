<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Contenir;
use App\Repository\CommandeRepository;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_panier_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Veuillez vous connecter pour voir votre panier.');
            return $this->redirectToRoute('app_login');
        }

        $commande = $commandeRepository->findOneBy([
            'utilisateur' => $user,
            'etat' => 'en cours',
        ]);

        if (!$commande) {
            $commande = new Commande();
            $commande->setUtilisateur($user)
                     ->setEtat('en cours')
                     ->setDateCommande(new \DateTime())
                     ->setPrixTotal(0);
            $this->entityManager->persist($commande);
            $this->entityManager->flush();
        }

        return $this->render('panier/index.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/add/{livreId}', name: 'app_panier_add', methods: ['POST'])]
public function addToCart(int $livreId, LivreRepository $livreRepository): Response
{
    $user = $this->getUser();
    if (!$user) return $this->redirectToRoute('app_login');
    $livre = $livreRepository->find($livreId);
    if (!$livre) return $this->redirectToRoute('app_livre_index');
    //  Bloquer si le livre n'a pas de fichier numérique
    if (!$livre->getFichierNumerique()) {
        $this->addFlash('danger', 'Ce livre n\'est pas disponible à l\'achat.');
        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }
    $commande = $this->entityManager->getRepository(Commande::class)->findOneBy([
        'utilisateur' => $user,
        'etat' => 'en cours',
    ]);
    if (!$commande) {
        $commande = new Commande();
        $commande->setUtilisateur($user)
                 ->setEtat('en cours')
                 ->setDateCommande(new \DateTime())
                 ->setPrixTotal(0);
        $this->entityManager->persist($commande);
    }
    $contenir = $this->entityManager->getRepository(Contenir::class)->findOneBy([
        'commande' => $commande,
        'livre' => $livre,
    ]);
    if ($contenir) {
        $contenir->setQuantite($contenir->getQuantite() + 1);
    } else {
        $contenir = new Contenir();
        $contenir->setLivre($livre)
                 ->setCommande($commande)
                 ->setQuantite(1);
        $commande->addContenir($contenir);
    }
    $commande->setPrixTotal($commande->getPrixTotal() + $livre->getPrix());
    $this->entityManager->flush();

    return $this->redirectToRoute('app_panier_index');
}

    #[Route('/{livreId}/decrement', name: 'app_panier_decrement', methods: ['POST'])]
    public function decrementQuantity(int $livreId, LivreRepository $livreRepository): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $livre = $livreRepository->find($livreId);
        if (!$livre) return $this->redirectToRoute('app_panier_index');

        $commande = $this->entityManager->getRepository(Commande::class)->findOneBy([
            'utilisateur' => $user,
            'etat' => 'en cours',
        ]);

        if (!$commande) return $this->redirectToRoute('app_panier_index');

        $contenir = $this->entityManager->getRepository(Contenir::class)->findOneBy([
            'commande' => $commande,
            'livre' => $livre,
        ]);

        if ($contenir) {
            $prixUnitaire = $livre->getPrix();
            if ($contenir->getQuantite() > 1) {
                $contenir->setQuantite($contenir->getQuantite() - 1);
                $commande->setPrixTotal($commande->getPrixTotal() - $prixUnitaire);
            } else {
                $commande->removeContenir($contenir);
                $commande->setPrixTotal($commande->getPrixTotal() - $prixUnitaire);
                $this->entityManager->remove($contenir);
            }

            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/clear', name: 'app_panier_clear', methods: ['POST'])]
    public function clearCart(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $commande = $this->entityManager->getRepository(Commande::class)->findOneBy([
            'utilisateur' => $user,
            'etat' => 'en cours',
        ]);

        if ($commande) {
            foreach ($commande->getContenirs() as $contenir) {
                $commande->setPrixTotal($commande->getPrixTotal() - $contenir->getQuantite() * $contenir->getLivre()->getPrix());
                $this->entityManager->remove($contenir);
            }
            $commande->setPrixTotal(0);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/{id}/payer', name: 'app_commande_payer', methods: ['POST'])]
    public function payCommande(int $id): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $user || $commande->getEtat() !== 'en cours') {
            return $this->redirectToRoute('app_panier_index');
        }

        $commande->setEtat('payée');
        $commande->setDatePaiement(new \DateTime());
        $this->entityManager->flush();

        return $this->redirectToRoute('app_commande_index');
    }

    #[Route('/valider', name: 'app_panier_valider', methods: ['POST'])]
public function validerPanier(EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    if (!$user) {
        $this->addFlash('danger', 'Vous devez être connecté.');
        return $this->redirectToRoute('app_login');
    }

    $commande = $entityManager->getRepository(Commande::class)->findOneBy([
        'utilisateur' => $user,
        'etat' => 'en cours',
    ]);

    if (!$commande || count($commande->getContenirs()) === 0) {
        $this->addFlash('danger', 'Votre panier est vide.');
        return $this->redirectToRoute('app_panier_index');
    }

    // ✅ NE PAS changer l'état ici
    // On garde "en cours", le paiement PayPal le changera en "payée"

    $this->addFlash('success', 'Commande enregistrée. Vous pouvez maintenant la payer.');
    return $this->redirectToRoute('app_commande_index');
}

}
