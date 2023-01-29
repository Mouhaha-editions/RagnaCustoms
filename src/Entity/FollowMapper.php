<?php

namespace App\Entity;

use App\Repository\FollowMapperRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: FollowMapperRepository::class)]
class FollowMapper
{

    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'followedMappers')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'followers')]
    #[ORM\JoinColumn(nullable: false)]
    private $mapper;

    #[ORM\Column(type: 'boolean')]
    private $isNotificationEnabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMapper(): ?Utilisateur
    {
        return $this->mapper;
    }

    public function setMapper(?Utilisateur $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    public function getIsNotificationEnabled(): ?bool
    {
        return $this->isNotificationEnabled;
    }

    public function setIsNotificationEnabled(bool $isNotificationEnabled): self
    {
        $this->isNotificationEnabled = $isNotificationEnabled;

        return $this;
    }

    public function isIsNotificationEnabled(): ?bool
    {
        return $this->isNotificationEnabled;
    }
}
