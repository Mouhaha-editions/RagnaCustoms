<?php

namespace App\Entity;

use App\Repository\DownloadCounterRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: DownloadCounterRepository::class)]
class DownloadCounter
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Song::class, inversedBy: 'downloadCounters')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private $song;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'downloadCounters')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSong(): ?Song
    {
        return $this->song;
    }

    public function setSong(?Song $song): self
    {
        $this->song = $song;

        return $this;
    }
    public function setUser(UserInterface $user):self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}
