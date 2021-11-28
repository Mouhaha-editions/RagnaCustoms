<?php

namespace App\Entity;

use App\Repository\UserScoreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserScoreRepository::class)
 */
class UserScore
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;



    /**
     * @ORM\Column(type="integer")
     */
    private $points;

    /**
     * @ORM\Column(type="integer")
     */
    private $runNumber;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="userScores")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     */
    private $challengeEdition;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function getRunNumber(): ?int
    {
        return $this->runNumber;
    }

    public function setRunNumber(int $runNumber): self
    {
        $this->runNumber = $runNumber;

        return $this;
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

    public function getChallengeEdition(): ?int
    {
        return $this->challengeEdition;
    }

    public function setChallengeEdition(int $challengeEdition): self
    {
        $this->challengeEdition = $challengeEdition;

        return $this;
    }
}
