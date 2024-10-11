<?php

namespace App\Entity;

use App\Repository\VoteCounterRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: VoteCounterRepository::class)]
class VoteCounter
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'voteCounter')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: Song::class, inversedBy: 'voteCounters')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $song;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $votes_indc;

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

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getVotesIndc(): ?bool
    {
        return $this->votes_indc;
    }

    public function setVotesIndc(?bool $votesIndc): self
    {
        $this->votes_indc = $votesIndc;

        return $this;
    }

    public function isVotesIndc(): ?bool
    {
        return $this->votes_indc;
    }
}
