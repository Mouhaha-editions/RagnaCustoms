<?php

namespace App\Entity;

use App\Repository\ChangelogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ChangelogRepository::class)]
class Changelog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $baseDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $premiumDescription = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, inversedBy: 'changelogs')]
    private Collection $readedBy;

    public function __construct()
    {
        $this->readedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseDescription(): ?string
    {
        return $this->baseDescription;
    }

    public function setBaseDescription(?string $baseDescription): static
    {
        $this->baseDescription = $baseDescription;

        return $this;
    }

    public function getPremiumDescription(): ?string
    {
        return $this->premiumDescription;
    }

    public function setPremiumDescription(?string $premiumDescription): static
    {
        $this->premiumDescription = $premiumDescription;

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getReadedBy(): Collection
    {
        return $this->readedBy;
    }

    public function addReadedBy(Utilisateur $readedBy): static
    {
        if (!$this->readedBy->contains($readedBy)) {
            $this->readedBy->add($readedBy);
        }

        return $this;
    }

    public function removeReadedBy(Utilisateur $readedBy): static
    {
        $this->readedBy->removeElement($readedBy);

        return $this;
    }
}
