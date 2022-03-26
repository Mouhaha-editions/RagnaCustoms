<?php

namespace App\Entity;

use App\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @ORM\Column(type="float", nullable=true)
     */
    private $flow;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $funFactor;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $levelQuality;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $patternQuality;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $readability;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $rhythm;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $disabled = false;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $feedback;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hash;
    /**
     * @ORM\Column(type="boolean")
     */
    private $isAnonymous = false;
    /**
     * @ORM\Column(type="boolean")
     */
    private $isModerated = false;
    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublic = true;
    /**
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="votes")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $song;
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="votes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @return mixed
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param mixed $feedback
     */
    public function setFeedback($feedback): void
    {
        $this->feedback = $feedback;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAverage()
    {
        if($this->getFlow()>0){
            return ($this->getFlow() +$this->getLevelQuality() +$this->getReadability() +  $this->getRhythm() + $this->getFunFactor() + $this->getPatternQuality()) / 6;
        }
        return ($this->getReadability() +  $this->getRhythm() + $this->getFunFactor() + $this->getPatternQuality()) / 4;
    }

    public function getLevelQuality(): ?float
    {
        return $this->levelQuality;
    }

    public function setLevelQuality(?float $levelQuality): self
    {
        $this->levelQuality = $levelQuality;

        return $this;
    }

    public function getReadability(): ?float
    {
        return $this->readability;
    }

    public function setReadability(?float $readability): self
    {
        $this->readability = $readability;

        return $this;
    }

    public function getFlow(): ?float
    {
        return $this->flow;
    }

    public function setFlow(?float $flow): self
    {
        $this->flow = $flow;

        return $this;
    }

    public function getRhythm(): ?float
    {
        return $this->rhythm;
    }

    public function setRhythm(?float $rhythm): self
    {
        $this->rhythm = $rhythm;

        return $this;
    }

    public function getFunFactor(): ?float
    {
        return $this->funFactor;
    }

    public function setFunFactor(?float $funFactor): self
    {
        $this->funFactor = $funFactor;

        return $this;
    }

    public function getPatternQuality(): ?float
    {
        return $this->patternQuality;
    }

    public function setPatternQuality(?float $patternQuality): self
    {
        $this->patternQuality = $patternQuality;

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

    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(?bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }


    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getIsAnonymous(): ?bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): self
    {
        $this->isAnonymous = $isAnonymous;

        return $this;
    }

    public function getIsModerated(): ?bool
    {
        return $this->isModerated;
    }

    public function setIsModerated(bool $isModerated): self
    {
        $this->isModerated = $isModerated;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

}
