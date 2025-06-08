<?php

namespace App\Entity;
use App\Repository\LivreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Categorie;

#[ORM\Entity(repositoryClass: LivreRepository::class)]
class Livre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: "text")]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null; // Image du livre

    #[ORM\ManyToMany(targetEntity: Categorie::class)]
    private Collection $categories;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: Contenir::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $contenirs;

   
    #[ORM\ManyToOne(inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Auteur $auteur = null;

    #[ORM\OneToMany(mappedBy: 'livre', targetEntity: Avis::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $avis;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fichierNumerique = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->contenirs = new ArrayCollection();
        
        $this->avis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Categorie $categorie): static
    {
        if (!$this->categories->contains($categorie)) {
            $this->categories->add($categorie);
        }

        return $this;
    }

    public function removeCategory(Categorie $categorie): static
    {
        $this->categories->removeElement($categorie);
        return $this;
    }

    public function getContenirs(): Collection
    {
        return $this->contenirs;
    }

    public function addContenir(Contenir $contenir): static
    {
        if (!$this->contenirs->contains($contenir)) {
            $this->contenirs->add($contenir);
            $contenir->setLivre($this);
        }
        return $this;
    }

    public function removeContenir(Contenir $contenir): static
    {
        if ($this->contenirs->removeElement($contenir)) {
            if ($contenir->getLivre() === $this) {
                $contenir->setLivre(null);
            }
        }
        return $this;
    }

    
    public function getAuteur(): ?Auteur
    {
        return $this->auteur;
    }

    public function setAuteur(?Auteur $auteur): static
    {
        $this->auteur = $auteur;
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
            $avi->setLivre($this);
        }
        return $this;
    }

    public function removeAvi(Avis $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            if ($avi->getLivre() === $this) {
                $avi->setLivre(null);
            }
        }
        return $this;
    }

    public function getFichierNumerique(): ?string
    {
        return $this->fichierNumerique;
    }

    public function setFichierNumerique(?string $fichierNumerique): static
    {
        $this->fichierNumerique = $fichierNumerique;
        return $this;
    }
}