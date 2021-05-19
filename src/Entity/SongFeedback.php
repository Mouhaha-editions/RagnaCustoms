<?php

namespace App\Entity;

use App\Repository\SongFeedbackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=SongFeedbackRepository::class)
 */
class SongFeedback
{
    use TimestampableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="songFeedback")
     * @ORM\JoinColumn(nullable=false)
     */
    private $song;

    /**
     * @ORM\ManyToOne(targetEntity=SongDifficulty::class, inversedBy="songFeedback")
     */
    private $songDifficulty;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublic= true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isAnonymous = false;

    /**
     * @ORM\Column(type="text")
     */
    private $feedback;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="songFeedback")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isModerated = false;

    /**
     * @ORM\ManyToOne(targetEntity=SongFeedback::class, inversedBy="songFeedback")
     */
    private $feedbackParent;

    /**
     * @ORM\OneToMany(targetEntity=SongFeedback::class, mappedBy="feedbackParent")
     */
    private $songFeedback;

    public function __construct()
    {
        $this->songFeedback = new ArrayCollection();
    }

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

    public function getSongDifficulty(): ?SongDifficulty
    {
        return $this->songDifficulty;
    }

    public function setSongDifficulty(?SongDifficulty $songDifficulty): self
    {
        $this->songDifficulty = $songDifficulty;

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

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(string $feedback): self
    {
        $this->feedback = $feedback;

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

    public function getIsModerated(): ?bool
    {
        return $this->isModerated;
    }

    public function setIsModerated(bool $isModerated): self
    {
        $this->isModerated = $isModerated;

        return $this;
    }

    public function getFeedbackParent(): ?self
    {
        return $this->feedbackParent;
    }

    public function setFeedbackParent(?self $feedbackParent): self
    {
        $this->feedbackParent = $feedbackParent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getSongFeedback(): Collection
    {
        return $this->songFeedback;
    }

    public function addSongFeedback(self $songFeedback): self
    {
        if (!$this->songFeedback->contains($songFeedback)) {
            $this->songFeedback[] = $songFeedback;
            $songFeedback->setFeedbackParent($this);
        }

        return $this;
    }

    public function removeSongFeedback(self $songFeedback): self
    {
        if ($this->songFeedback->removeElement($songFeedback)) {
            // set the owning side to null (unless already changed)
            if ($songFeedback->getFeedbackParent() === $this) {
                $songFeedback->setFeedbackParent(null);
            }
        }

        return $this;
    }
}
