<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
#[ORM\HasLifecycleCallbacks]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commentaire')]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $note = null;

    #[ORM\Column(length: 500)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'date_commentaire', type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCommentaire = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente';

    #[ORM\ManyToOne(targetEntity: Personnage::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'id_personnage', referencedColumnName: 'id_personnage', nullable: false, onDelete: 'CASCADE')]
    private ?Personnage $personnage = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false, onDelete: 'CASCADE')]
    private ?User $auteur = null;

    public function __construct()
    {
        $this->dateCommentaire = new \DateTimeImmutable();
        $this->statut = 'en_attente';
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->dateCommentaire === null) {
            $this->dateCommentaire = new \DateTimeImmutable();
        }
        if ($this->statut === null) {
            $this->statut = 'en_attente';
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateCommentaire(): ?\DateTimeImmutable
    {
        return $this->dateCommentaire;
    }

    public function setDateCommentaire(\DateTimeImmutable $dateCommentaire): static
    {
        $this->dateCommentaire = $dateCommentaire;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage): static
    {
        $this->personnage = $personnage;
        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function isEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function isApprouve(): bool
    {
        return $this->statut === 'approuve';
    }

    public function isRefuse(): bool
    {
        return $this->statut === 'refuse';
    }
}
