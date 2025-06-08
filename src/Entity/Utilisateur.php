<?php

namespace App\Entity;

use App\Enum\UtilisateurRole;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column(enumType: UtilisateurRole::class)]
    private ?UtilisateurRole $role = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDesactivation = null;

    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'utilisateur')]
    private Collection $avis;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'utilisateur')]
    private Collection $commandes;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getRole(): ?UtilisateurRole
    {
        return $this->role;
    }

    public function setRole(UtilisateurRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getDateDesactivation(): ?\DateTimeInterface
    {
        return $this->dateDesactivation;
    }

    public function setDateDesactivation(?\DateTimeInterface $dateDesactivation): static
    {
        $this->dateDesactivation = $dateDesactivation;
        return $this;
    }

    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvi(Avis $avi): static
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            if ($avi->getUtilisateur() === $this) {
                $avi->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setUtilisateur($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getUtilisateur() === $this) {
                $commande->setUtilisateur(null);
            }
        }

        return $this;
    }

    // 🔐 Implémentation des interfaces de sécurité :

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    public function getRoles(): array
    {
        return ['ROLE_' . strtoupper($this->role->value)];
    }

    public function eraseCredentials(): void
    {
        // nettoyer les infos sensibles ici si besoin
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }
}
