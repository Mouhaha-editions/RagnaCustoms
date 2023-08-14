<?php

namespace App\Entity;

use App\Repository\CustomEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CustomEventRepository::class)]
class CustomEvent
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $banner = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxChallenger = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $openningDateRegistration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $closingDateRegistration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rules = null;

    #[ORM\ManyToOne(inversedBy: 'customEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\Column]
    private bool $enabled = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $edition = null;

    #[ORM\OneToMany(mappedBy: 'customEvent', targetEntity: CustomEventParticipation::class)]
    private Collection $customEventParticipations;

    public function __construct()
    {
        $this->customEventParticipations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(string $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function getMaxChallenger(): ?int
    {
        return $this->maxChallenger;
    }

    public function setMaxChallenger(?int $maxChallenger): static
    {
        $this->maxChallenger = $maxChallenger;

        return $this;
    }

    public function getOpenningDateRegistration(): ?\DateTimeInterface
    {
        return $this->openningDateRegistration;
    }

    public function setOpenningDateRegistration(\DateTimeInterface $openningDateRegistration): static
    {
        $this->openningDateRegistration = $openningDateRegistration;

        return $this;
    }

    public function getClosingDateRegistration(): ?\DateTimeInterface
    {
        return $this->closingDateRegistration;
    }

    public function setClosingDateRegistration(\DateTimeInterface $closingDateRegistration): static
    {
        $this->closingDateRegistration = $closingDateRegistration;

        return $this;
    }

    public function getRules(): ?string
    {
        return $this->rules;
    }

    public function setRules(?string $rules): static
    {
        $this->rules = $rules;

        return $this;
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

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getEdition(): ?string
    {
        return $this->edition;
    }

    public function setEdition(?string $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    /**
     * @return Collection<int, CustomEventParticipation>
     */
    public function getCustomEventParticipations(): Collection
    {
        return $this->customEventParticipations;
    }

    public function addCustomEventParticipation(CustomEventParticipation $customEventParticipation): static
    {
        if (!$this->customEventParticipations->contains($customEventParticipation)) {
            $this->customEventParticipations->add($customEventParticipation);
            $customEventParticipation->setCustomEvent($this);
        }

        return $this;
    }

    public function removeCustomEventParticipation(CustomEventParticipation $customEventParticipation): static
    {
        if ($this->customEventParticipations->removeElement($customEventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($customEventParticipation->getCustomEvent() === $this) {
                $customEventParticipation->setCustomEvent(null);
            }
        }

        return $this;
    }
}
