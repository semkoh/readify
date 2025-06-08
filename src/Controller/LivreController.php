<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Livre;
use App\Entity\Commande;
use App\Entity\Contenir;
use App\Form\LivreType;
use App\Form\AvisType;
use App\Entity\Avis;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/livre')]
class LivreController extends AbstractController
{
    #[Route('/', name: 'app_livre_index')]
    public function index(LivreRepository $livreRepository, Request $request): Response
    {
        $searchTerm = $request->query->get('search');
        $livres = $searchTerm ? $livreRepository->findBySearchTerm($searchTerm) : $livreRepository->findAll();

        return $this->render('livre/index.html.twig', [
            'livres' => $livres,
        ]);
    }

    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $livre = new Livre();
    $form = $this->createForm(LivreType::class, $livre);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Gestion image (URL)
        $image = $form->get('image')->getData();
        if ($image) {
            $livre->setImage($image);
        }

        // Gestion fichier numérique
        $fichier = $form->get('fichierNumerique')->getData();
        if ($fichier) {
            $nomFichier = uniqid() . '.' . $fichier->guessExtension();
            $fichier->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/livres',
                $nomFichier
            );
            $livre->setFichierNumerique($nomFichier);
        }

        $entityManager->persist($livre);
        $entityManager->flush();

        return $this->redirectToRoute('app_livre_index');
    }

    return $this->render('livre/new.html.twig', [
        'livre' => $livre,
        'form' => $form->createView(),
    ]);
}


    #[Route('/{id}', name: 'app_livre_show', methods: ['GET', 'POST'])]
public function show(Livre $livre, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
{
    $avis = new Avis();
    $avis->setLivre($livre);
    $avis->setUtilisateur($this->getUser());
    $avis->setDateAvis(new \DateTime());

    $form = $this->createForm(AvisType::class, $avis);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($avis);
        $entityManager->flush();
        $this->addFlash('success', 'Votre avis a été ajouté.');
        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }

    $avisList = $entityManager->getRepository(Avis::class)->findBy(['livre' => $livre]);

    //  Vérifie si l’utilisateur connecté a une commande payée avec ce livre
    $peutTelecharger = false;
    if ($this->getUser()) {
        $commandesPayees = $entityManager->getRepository(Commande::class)->findBy([
            'utilisateur' => $this->getUser(),
            'etat' => 'payée'
        ]);

        foreach ($commandesPayees as $commande) {
            foreach ($commande->getContenirs() as $contenir) {
                if ($contenir->getLivre() === $livre) {
                    $peutTelecharger = true;
                    break 2;
                }
            }
        }
    }

    return $this->render('livre/show.html.twig', [
        'livre' => $livre,
        'form' => $form->createView(),
        'avisList' => $avisList,
        'peutTelecharger' => $peutTelecharger, // 👈 passer à Twig
    ]);
}

   #[Route('/edit/{id}', name: 'app_livre_edit', methods: ['GET', 'POST'])]
public function edit(Livre $livre, Request $request, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $form = $this->createForm(LivreType::class, $livre);
    $form->handleRequest($request);

    $ancienFichier = $livre->getFichierNumerique();

    if ($form->isSubmitted() && $form->isValid()) {
        $uploadedFile = $form->get('fichierNumerique')->getData();

        if ($uploadedFile) {
            // On construit le nom en fonction du titre
            $nomFichier = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $livre->getTitre()))) . '.' . $uploadedFile->guessExtension();

            $chemin = $this->getParameter('kernel.project_dir') . '/public/uploads/livres/' . $nomFichier;

            // ⚠ Supprime l'ancien fichier uniquement s’il a un nom différent
            if ($ancienFichier && $ancienFichier !== $nomFichier && file_exists($this->getParameter('kernel.project_dir') . '/public/uploads/livres/' . $ancienFichier)) {
                unlink($this->getParameter('kernel.project_dir') . '/public/uploads/livres/' . $ancienFichier);
            }

            // ⚠ Si un fichier avec le même nom existe déjà, le nouveau le remplace
            $uploadedFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/livres',
                $nomFichier
            );

            $livre->setFichierNumerique($nomFichier);
        } else {
            // Aucun fichier uploadé, on garde le précédent
            $livre->setFichierNumerique($ancienFichier);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Livre modifié avec succès.');
        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }

    return $this->render('livre/edit.html.twig', [
        'livre' => $livre,
        'form' => $form->createView(),
    ]);
}




    // 🔁 Méthode d'envoi de mail
    private function envoyerLivre(MailerInterface $mailer, Livre $livre): void
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            $this->addFlash('danger', 'Utilisateur non authentifié.');
            return;
        }

        $fichier = $livre->getFichierNumerique();
        if (!$fichier) {
            $this->addFlash('danger', 'Aucun fichier à envoyer.');
            return;
        }

        $downloadLink = $this->generateUrl('app_livre_download', ['id' => $livre->getId()], true);

        $email = (new Email())
            ->from('admin@livrestore.com')
            ->to($user->getEmail())
            ->subject('Votre livre numérique')
            ->html("<p>Merci pour votre commande. Cliquez ici pour <a href='$downloadLink'>télécharger votre livre</a>.</p>");

        $mailer->send($email);
    }

    // 🔽 Téléchargement du fichier via ID du livre
    #[Route('/{id}/download', name: 'app_livre_download', methods: ['GET'])]
    public function download(Livre $livre): Response
    {
        $fichier = $livre->getFichierNumerique();
        if (!$fichier) {
            throw $this->createNotFoundException('Aucun fichier numérique disponible pour ce livre.');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/livres/' . $fichier;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier n\'existe pas.');
        }

        return $this->file($filePath);
    }
    #[Route('/{id}', name: 'app_livre_delete', methods: ['POST'])]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $livre->getId(), $request->request->get('_token'))) {

            // Supprimer les contenirs manuellement
            foreach ($livre->getContenirs() as $contenir) {
                $entityManager->remove($contenir);
            }

            // Supprimer les avis manuellement
            foreach ($livre->getAvis() as $avis) {
                $entityManager->remove($avis);
            }

            $entityManager->flush(); // important de flush ici pour supprimer les enfants

            // Supprimer ensuite le livre
            $entityManager->remove($livre);
            $entityManager->flush();

            $this->addFlash('success', 'Livre supprimé avec succès.');
        }

        return $this->redirectToRoute('app_livre_index');
    }


}
