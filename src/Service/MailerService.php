<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Envoie un email HTML simple sans pièce jointe
     */
    public function send(
        string $to,
        string $subject,
        string $content,
        string $from = 'readifylivre@gmail.com'
    ): void {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($content);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email avec une pièce jointe (ex: livre PDF)
     */
    public function sendWithAttachment(
        string $to,
        string $subject,
        string $content,
        string $filePath,
        string $filename = 'Hamlet.pdf',
        string $from = 'readifylivre@gmail.com'
    ): void {
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($content)
            ->attachFromPath($filePath, $filename);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email spécifique avec un livre numérique acheté en pièce jointe
     */
    public function sendBookPurchaseEmail(string $to, string $username, string $pdfPath, string $bookTitle): void
    {
        $subject = ' Readify - Votre livre est prêt à être lu !';
        $content = <<<HTML
            <p>Bonjour <strong>{$username}</strong>,</p>
            <p>Merci pour votre achat sur <strong>Readify</strong> !</p>
            <p>Vous trouverez ci-joint votre livre numérique <strong>{$bookTitle}</strong> au format PDF.</p>
            <p>Bonne lecture et à bientôt sur <strong>Readify.com</strong> !</p>
        HTML;

        $this->sendWithAttachment($to, $subject, $content, $pdfPath, $bookTitle . '.pdf');
    }
}
