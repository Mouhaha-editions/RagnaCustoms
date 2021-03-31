<?php

namespace App\Entity;

use App\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=VoteRepository::class)
 */
class Vote
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $song;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $FunFactor;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Rhythm;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Flow;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $PatternQuality;

    /**
     * @ORM\Column(type="float")
     */
    private $Readability;

    /**
     * @ORM\Column(type="float")
     */
    private $LevelQuality;

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

    public function getSong(): ?Song
    {
        return $this->song;
    }

    public function setSong(?Song $song): self
    {
        $this->song = $song;

        return $this;
    }

    public function getFunFactor(): ?float
    {
        return $this->FunFactor;
    }

    public function setFunFactor(?float $FunFactor): self
    {
        $this->FunFactor = $FunFactor;

        return $this;
    }

    public function getRhythm(): ?float
    {
        return $this->Rhythm;
    }

    public function setRhythm(?float $Rhythm): self
    {
        $this->Rhythm = $Rhythm;

        return $this;
    }

    public function getFlow(): ?float
    {
        return $this->Flow;
    }

    public function setFlow(?float $Flow): self
    {
        $this->Flow = $Flow;

        return $this;
    }

    public function getPatternQuality(): ?float
    {
        return $this->PatternQuality;
    }

    public function setPatternQuality(?float $PatternQuality): self
    {
        $this->PatternQuality = $PatternQuality;

        return $this;
    }

    public function getReadability(): ?float
    {
        return $this->Readability;
    }

    public function setReadability(float $Readability): self
    {
        $this->Readability = $Readability;

        return $this;
    }

    public function getLevelQuality(): ?float
    {
        return $this->LevelQuality;
    }

    public function setLevelQuality(float $LevelQuality): self
    {
        $this->LevelQuality = $LevelQuality;

        return $this;
    }
}
