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
    private ?string $description = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, inversedBy: 'changelogs')]
    private Collection $readBy;

    #[ORM\Column]
    private ?bool $isWip = true;

    public function __construct()
    {
        $this->readBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getReadBy(): Collection
    {
        return $this->readBy;
    }

    public function addReadBy(Utilisateur $readedBy): static
    {
        if (!$this->readBy->contains($readedBy)) {
            $this->readBy->add($readedBy);
        }

        return $this;
    }

    public function removeReadBy(Utilisateur $readedBy): static
    {
        $this->readBy->removeElement($readedBy);

        return $this;
    }

    public function isWip(): ?bool
    {
        return $this->isWip;
    }

    public function setIsWip(bool $isWip): static
    {
        $this->isWip = $isWip;

        return $this;
    }
}
