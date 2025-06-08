<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\EtatCommande;  // Importation de l'énumération

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column]
    private ?float $prixTotal = null;

    #[ORM\Column(length: 50)]
    private ?string $etat = EtatCommande::EN_COURS;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datePaiement = null; // Nullable for payment date

    #[ORM\Column(length: 50)]
    private ?string $methodePaiement = 'paypal';

    /**
     * @var Collection<int, Contenir>
     */
    #[ORM\OneToMany(targetEntity: Contenir::class, mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private Collection $contenirs;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    private ?Utilisateur $utilisateur = null;

    public function __construct()
    {
        $this->contenirs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(float $prixTotal): static
    {
        $this->prixTotal = $prixTotal;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getMethodePaiement(): ?string
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(string $methodePaiement): static
    {
        $this->methodePaiement = $methodePaiement;

        return $this;
    }

    /**
     * @return Collection<int, Contenir>
     */
    public function getContenirs(): Collection
    {
        return $this->contenirs;
    }

    public function addContenir(Contenir $contenir): static
    {
        if (!$this->contenirs->contains($contenir)) {
            $this->contenirs->add($contenir);
            $contenir->setCommande($this);
        }

        return $this;
    }

    public function removeContenir(Contenir $contenir): static
    {
        if ($this->contenirs->removeElement($contenir)) {
            // set the owning side to null (unless already changed)
            if ($contenir->getCommande() === $this) {
                $contenir->setCommande(null);
            }
        }

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    // Ajouter la méthode getLivres() ici
    public function getLivres(): Collection
    {
        $livres = new ArrayCollection();
        foreach ($this->contenirs as $contenir) {
            $livres[] = $contenir->getLivre();  // Ajouter chaque livre lié à la commande
        }
        return $livres;
    }
}
