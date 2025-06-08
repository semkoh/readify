<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Service\PayPalService;
use App\Service\MailerService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/paypal')]
class PayPalController extends AbstractController
{
    private PayPalService $paypalService;
    private ManagerRegistry $doctrine;
    private MailerService $mailerService;

    public function __construct(
        PayPalService $paypalService,
        ManagerRegistry $doctrine,
        MailerService $mailerService
    ) {
        $this->paypalService = $paypalService;
        $this->doctrine = $doctrine;
        $this->mailerService = $mailerService;
    }

    #[Route('/create/{orderId}', name: 'app_paypal_create_payment')]
    public function createPayment($orderId): Response
    {
        $order = $this->doctrine->getRepository(Commande::class)->find($orderId);

        if (!$order) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirectToRoute('app_panier_index');
        }

        if ($order->getEtat() !== 'en cours') {
            $order->setEtat('en cours');
            $this->doctrine->getManager()->flush();
        }

        $payment = $this->paypalService->createPayment($order->getPrixTotal(), 'USD', $order->getId());

        if ($payment) {
            foreach ($payment->getLinks() as $link) {
                if ($link->getRel() === 'approval_url') {
                    return $this->redirect($link->getHref());
                }
            }
        }

        $this->addFlash('danger', 'Erreur lors de la création du paiement.');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/success', name: 'app_paypal_success')]
    public function success(Request $request, EntityManagerInterface $entityManager): Response
    {
        $paymentId = $request->get('paymentId');
        $payerId = $request->get('PayerID');
        $orderId = $request->get('orderId');

        $commande = $entityManager->getRepository(Commande::class)->find($orderId);

        if (!$commande) {
            $this->addFlash('danger', 'Commande non trouvée.');
            return $this->redirectToRoute('app_commande_index');
        }

        try {
            $payment = $this->paypalService->executePayment($paymentId, $payerId);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
            return $this->redirectToRoute('app_commande_index');
        }

        if ($payment && $payment->getState() === 'approved') {
            $commande->setEtat('payée');
            $commande->setDatePaiement(new \DateTime());
            $commande->setMethodePaiement('paypal');
            $entityManager->flush();

            //  Envoi de l'e-mail avec pièce jointe pour chaque livre
            foreach ($commande->getContenirs() as $contenir) {
                $livre = $contenir->getLivre();
                $fichier = $livre->getFichierNumerique();

                if ($fichier) {
                    $cheminFichier = $this->getParameter('kernel.project_dir') . '/public/uploads/livres/' . $fichier;
                    $lienTelechargement = $this->generateUrl(
                        'app_livre_download',
                        ['id' => $livre->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    $htmlContent = "<p><strong>{$livre->getTitre()}</strong> est bien disponible. Merci pour votre achat !</p>
                                    <p><a href='$lienTelechargement'> Télécharger le livre</a></p>";

                    if (file_exists($cheminFichier)) {
                        $this->mailerService->sendBookPurchaseEmail(
                            $commande->getUtilisateur()->getEmail(),
                            $commande->getUtilisateur()->getPrenom() . ' ' . $commande->getUtilisateur()->getNom(),
                            $cheminFichier,
                            $livre->getTitre()
                        );
                    }

                }
            }

            $this->addFlash('success', 'Paiement réussi ! Vos livres ont été envoyés par email.');
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }

        $this->addFlash('danger', 'Le paiement n’a pas été approuvé.');
        return $this->redirectToRoute('app_commande_index');
    }

    #[Route('/cancel', name: 'app_paypal_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('danger', 'Le paiement a été annulé.');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/test-mail', name: 'app_test_mail')]
public function testMail(): Response
{
    $cheminFichier = $this->getParameter('kernel.project_dir') . '/public/uploads/livres/madame_bovary.pdf';

    if (!file_exists($cheminFichier)) {
        return new Response(" Fichier non trouvé.");
    }

    $this->mailerService->sendWithAttachment(
        'readifylivre@gmail.com',  // remplace par ton vrai email
        'Test - Livre numérique',
        '<p>Voici votre livre en pièce jointe.</p>',
        $cheminFichier,
        'madame_bovary.pdf'
    );

    return new Response(' Mail de test envoyé !');
}

}
