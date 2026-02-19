<?php

namespace App\Entity;

use App\Repository\PersonnageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonnageRepository::class)]
#[ORM\Table(name: 'personnage')]
#[ORM\HasLifecycleCallbacks]
class Personnage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_personnage')]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    private ?string $genre = null;

    #[ORM\Column(name: 'couleur_peau', length: 50)]
    private ?string $couleurPeau = null;

    #[ORM\Column(name: 'couleur_yeux', length: 50)]
    private ?string $couleurYeux = null;

    #[ORM\Column(name: 'couleur_cheveux', length: 50)]
    private ?string $couleurCheveux = null;

    #[ORM\Column(name: 'forme_yeux', length: 50)]
    private ?string $formeYeux = null;

    #[ORM\Column(name: 'forme_nez', length: 50)]
    private ?string $formeNez = null;

    #[ORM\Column(name: 'forme_bouche', length: 50)]
    private ?string $formeBouche = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $partage = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $autorise = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $image = null;

    #[ORM\Column(name: 'date_creation', nullable: true)]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'personnages')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'personnage', cascade: ['remove'], orphanRemoval: true)]
    private Collection $commentaires;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'personnages')]
    private Collection $articles;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
        $this->partage = false;
        $this->autorise = null;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->dateCreation === null) {
            $this->dateCreation = new \DateTimeImmutable();
        }
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

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): static
    {
        $this->genre = $genre;
        return $this;
    }

    public function getCouleurPeau(): ?string
    {
        return $this->couleurPeau;
    }

    public function setCouleurPeau(string $couleurPeau): static
    {
        $this->couleurPeau = $couleurPeau;
        return $this;
    }

    public function getCouleurYeux(): ?string
    {
        return $this->couleurYeux;
    }

    public function setCouleurYeux(string $couleurYeux): static
    {
        $this->couleurYeux = $couleurYeux;
        return $this;
    }

    public function getCouleurCheveux(): ?string
    {
        return $this->couleurCheveux;
    }

    public function setCouleurCheveux(string $couleurCheveux): static
    {
        $this->couleurCheveux = $couleurCheveux;
        return $this;
    }

    public function getFormeYeux(): ?string
    {
        return $this->formeYeux;
    }

    public function setFormeYeux(string $formeYeux): static
    {
        $this->formeYeux = $formeYeux;
        return $this;
    }

    public function getFormeNez(): ?string
    {
        return $this->formeNez;
    }

    public function setFormeNez(string $formeNez): static
    {
        $this->formeNez = $formeNez;
        return $this;
    }

    public function getFormeBouche(): ?string
    {
        return $this->formeBouche;
    }

    public function setFormeBouche(string $formeBouche): static
    {
        $this->formeBouche = $formeBouche;
        return $this;
    }

    public function isPartage(): bool
    {
        return $this->partage;
    }

    public function setPartage(bool $partage): static
    {
        $this->partage = $partage;
        return $this;
    }

    public function isAutorise(): ?bool
    {
        return $this->autorise;
    }

    public function setAutorise(?bool $autorise): static
    {
        $this->autorise = $autorise;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): static
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Get image as base64 data URL for display
     */
    public function getImageDataUrl(): ?string
    {
        if ($this->image === null) {
            return null;
        }

        if (is_resource($this->image)) {
            $imageData = stream_get_contents($this->image);
            rewind($this->image);
        } else {
            $imageData = $this->image;
        }

        // Détecte SVG (texte XML) vs PNG (binaire) — le SVG commence toujours par '<'
        $mimeType = str_starts_with(ltrim((string) $imageData), '<') ? 'image/svg+xml' : 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode((string) $imageData);
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * Alias for backward compatibility
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setPersonnage($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getPersonnage() === $this) {
                $commentaire->setPersonnage(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->addPersonnage($this);
        }
        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            $article->removePersonnage($this);
        }
        return $this;
    }
}
