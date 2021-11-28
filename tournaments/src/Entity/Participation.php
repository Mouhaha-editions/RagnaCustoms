<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ParticipationRepository::class)
 */
class Participation
{
    use TimestampableEntity;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="participations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Challenge::class, inversedBy="participations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $challenge;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="arbitreOf")
     */
    private $arbitre;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $OpenChallenge;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $CloseChallenge;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): self
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getArbitre(): ?User
    {
        return $this->arbitre;
    }

    public function setArbitre(?User $arbitre): self
    {
        $this->arbitre = $arbitre;

        return $this;
    }

    public function getOpenChallenge(): ?\DateTimeInterface
    {
        return $this->OpenChallenge;
    }

    public function setOpenChallenge(?\DateTimeInterface $OpenChallenge): self
    {
        $this->OpenChallenge = $OpenChallenge;

        return $this;
    }

    public function getCloseChallenge(): ?\DateTimeInterface
    {
        return $this->CloseChallenge;
    }

    public function setCloseChallenge(?\DateTimeInterface $CloseChallenge): self
    {
        $this->CloseChallenge = $CloseChallenge;

        return $this;
    }
}
