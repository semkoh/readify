<?php

namespace App\Service;

use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Commande;

class PayPalService
{
    private $apiContext;

    public function __construct(string $clientId, string $secret)
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential($clientId, $secret)
        );

        $this->apiContext->setConfig([
            'mode' => 'sandbox', // change to 'live' for production
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled' => false,
        ]);
    }

    public function createPayment($amount, $currency, $orderId)
{
    $payer = new Payer();
    $payer->setPaymentMethod("paypal");

    $amountObj = new Amount();
    $amountObj->setTotal(number_format($amount, 2, '.', '')); // exemple: "19.00"
    $amountObj->setCurrency($currency);

    $transaction = new Transaction();
    $transaction->setAmount($amountObj);
    $transaction->setDescription("Commande #$orderId");

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl("http://127.0.0.1:8000/paypal/success?orderId=$orderId")
                 ->setCancelUrl("http://127.0.0.1:8000/paypal/cancel");

    $payment = new Payment();
    $payment->setIntent(strval("sale"))

            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]); // 👈 tableau avec UN SEUL objet Transaction

            try {
                dump($payment->toJSON()); // ← pour debugger
                $payment->create($this->apiContext);
                return $payment;
            } catch (\Exception $e) {
                dump($e->getMessage());
                return null;
            }
            
}

    public function executePayment($paymentId, $payerId)
    {
        $payment = Payment::get($paymentId, $this->apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);
        return $payment->execute($execution, $this->apiContext);
    }

    public function sendBookToUser(Commande $order, MailerInterface $mailer)
    {
        $livre = $order->getContenirs()->first()?->getLivre();
        if (!$livre || !$livre->getFichierNumerique()) {
            return;
        }

        $email = (new Email())
            ->from('admin@livres.com')
            ->to($order->getUtilisateur()->getEmail())
            ->subject('Votre livre numérique')
            ->text('Merci pour votre commande. Vous trouverez ci-joint votre livre.')
            ->attachFromPath($livre->getFichierNumerique());

        $mailer->send($email);
    }
}
