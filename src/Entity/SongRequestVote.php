<?php

namespace App\Entity;

use App\Repository\SongRequestVoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=SongRequestVoteRepository::class)
 */
class SongRequestVote
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SongRequest::class, inversedBy="songRequestVotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $songRequest;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="songRequestVotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSongRequest(): ?SongRequest
    {
        return $this->songRequest;
    }

    public function setSongRequest(?SongRequest $songRequest): self
    {
        $this->songRequest = $songRequest;

        return $this;
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
}
