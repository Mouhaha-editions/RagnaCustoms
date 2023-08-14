<?php

namespace App\Entity;

use App\Repository\CustomEventParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CustomEventParticipationRepository::class)]
class CustomEventParticipation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customEventParticipations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\ManyToOne(inversedBy: 'customEventParticipations')]
    private ?CustomEvent $customEvent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 4, nullable: true)]
    private ?string $currentScore = null;

    #[ORM\Column]
    private ?bool $registrationValidated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCustomEvent(): ?CustomEvent
    {
        return $this->customEvent;
    }

    public function setCustomEvent(?CustomEvent $customEvent): static
    {
        $this->customEvent = $customEvent;

        return $this;
    }

    public function getCurrentScore(): ?string
    {
        return $this->currentScore;
    }

    public function setCurrentScore(?string $currentScore): static
    {
        $this->currentScore = $currentScore;

        return $this;
    }

    public function isRegistrationValidated(): ?bool
    {
        return $this->registrationValidated;
    }

    public function setRegistrationValidated(bool $registrationValidated): static
    {
        $this->registrationValidated = $registrationValidated;

        return $this;
    }
}
