<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\LivreRepository;

class PanierService
{
    public function __construct(
        private SessionInterface $session,
        private LivreRepository $livreRepository
    ) {}

    public function add(int $livreId): void
    {
        $panier = $this->session->get('panier', []);
        $panier[$livreId] = ($panier[$livreId] ?? 0) + 1;
        $this->session->set('panier', $panier);
    }

    public function remove(int $livreId): void
    {
        $panier = $this->session->get('panier', []);
        unset($panier[$livreId]);
        $this->session->set('panier', $panier);
    }

    public function decrement(int $livreId): void
    {
        $panier = $this->session->get('panier', []);
        if (!isset($panier[$livreId])) return;

        if ($panier[$livreId] > 1) {
            $panier[$livreId]--;
        } else {
            unset($panier[$livreId]);
        }

        $this->session->set('panier', $panier);
    }

    public function getPanierDetail(): array
    {
        $panier = $this->session->get('panier', []);
        $details = [];

        foreach ($panier as $id => $qty) {
            $livre = $this->livreRepository->find($id);
            if ($livre) {
                $details[] = [
                    'livre' => $livre,
                    'quantite' => $qty,
                ];
            }
        }

        return $details;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getPanierDetail() as $item) {
            $total += $item['livre']->getPrix() * $item['quantite'];
        }

        return $total;
    }

    public function clear(): void
    {
        $this->session->remove('panier');
    }
}
