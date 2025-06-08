<?php
// AvisController.php
namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Livre;
use App\Form\AvisType;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvisController extends AbstractController
{
    private $entityManager;

    // Injecter l'EntityManager dans le contrôleur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Route pour afficher et créer un nouvel avis
    #[Route('/avis/{id}/new', name: 'app_avis_new')]
    public function new(Request $request, LivreRepository $livreRepository, int $id): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le livre par ID
        $livre = $livreRepository->find($id);

        // Vérifier si le livre existe
        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé.');
        }

        // Créer un nouvel objet Avis
        $avis = new Avis();

        // Pré-remplir l'avis (utilisateur connecté, livre concerné)
        $avis->setLivre($livre);
        $avis->setUtilisateur($this->getUser()); // Assurez-vous que l'utilisateur est connecté
        $avis->setDateAvis(new \DateTime()); // Ajouter la date de l'avis automatiquement

        // Créer le formulaire
        $form = $this->createForm(AvisType::class, $avis);

        // Traiter la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder l'avis dans la base de données
            $this->entityManager->persist($avis);
            $this->entityManager->flush();

            // Rediriger vers la page de détails du livre
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        // Afficher le formulaire dans le template
        return $this->render('avis/new.html.twig', [
            'form' => $form->createView(),
            'livre' => $livre,
        ]);
    }

    // Route pour afficher les détails d'un avis
    #[Route('/avis/{id}', name: 'app_avis_show')]
    public function show(Avis $avis): Response
    {
        // Afficher les détails de l'avis
        return $this->render('avis/show.html.twig', [
            'avis' => $avis,
        ]);
    }

    // Route pour modifier un avis
    #[Route('/avis/{id}/edit', name: 'app_avis_edit')]
    public function edit(Request $request, Avis $avis): Response
    {
        // Assurez-vous que l'utilisateur est connecté et que c'est son avis
        $this->denyAccessUnlessGranted('EDIT', $avis); // Utiliser la permission d'édition

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder les modifications de l'avis dans la base de données
            $this->entityManager->flush();

            // Rediriger vers la page du livre après la modification de l'avis
            return $this->redirectToRoute('app_livre_show', ['id' => $avis->getLivre()->getId()]);
        }

        // Afficher le formulaire d'édition
        return $this->render('avis/edit.html.twig', [
            'form' => $form->createView(),
            'avis' => $avis,
        ]);
    }

    // Route pour supprimer un avis
    #[Route('/avis/{id}/delete', name: 'app_avis_delete', methods: ['POST'])]
    public function delete(Request $request, Avis $avis): Response
    {
        // Assurez-vous que l'utilisateur est connecté et que c'est son avis
        $this->denyAccessUnlessGranted('DELETE', $avis); // Utiliser la permission de suppression

        if ($this->isCsrfTokenValid('delete'.$avis->getId(), $request->request->get('_token'))) {
            // Supprimer l'avis de la base de données
            $this->entityManager->remove($avis);
            $this->entityManager->flush();
        }

        // Rediriger vers la page du livre après la suppression de l'avis
        return $this->redirectToRoute('app_livre_show', ['id' => $avis->getLivre()->getId()]);
    }
}
