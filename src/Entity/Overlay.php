<?php

namespace App\Entity;

use App\Repository\OverlayRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OverlayRepository::class)
 */
class Overlay
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Utilisateur::class, inversedBy="overlay", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToOne(targetEntity=SongDifficulty::class, cascade={"persist", "remove"})
     */
    private $difficulty;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $disposition;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(Utilisateur $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDifficulty(): ?SongDifficulty
    {
        return $this->difficulty;
    }

    public function setDifficulty(?SongDifficulty $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getDisposition(): ?string
    {
        return $this->disposition;
    }

    public function setDisposition(?string $disposition): self
    {
        $this->disposition = $disposition;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }
}
