<?php

namespace App\Entity;

use App\Repository\ScoreHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScoreHistoryRepository::class)
 */
class ScoreHistory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    use TimestampableEntity;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="scoreHistories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="float")
     */
    private $score;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $difficulty;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesHit;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesMissed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesNotProcessed;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $hitAccuracy;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $percentage;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $percentage2;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $hitSpeed;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $combos;

    /**
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="scoreHistories")
     */
    private $song;

    /**
     * @ORM\ManyToOne(targetEntity=SongDifficulty::class, inversedBy="scoreHistories")
     */
    private $songDifficulty;

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

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }


    public function getNotesHit(): ?int
    {
        return $this->notesHit;
    }

    public function setNotesHit(?int $notesHit): self
    {
        $this->notesHit = $notesHit;

        return $this;
    }

    public function getNotesMissed(): ?int
    {
        return $this->notesMissed;
    }

    public function setNotesMissed(?int $notesMissed): self
    {
        $this->notesMissed = $notesMissed;

        return $this;
    }

    public function getNotesNotProcessed(): ?int
    {
        return $this->notesNotProcessed;
    }

    public function setNotesNotProcessed(?int $notesNotProcessed): self
    {
        $this->notesNotProcessed = $notesNotProcessed;

        return $this;
    }

    public function getHitAccuracy(): ?string
    {
        return $this->hitAccuracy;
    }

    public function setHitAccuracy(?string $hitAccuracy): self
    {
        $this->hitAccuracy = $hitAccuracy;

        return $this;
    }

    public function getPercentage(): ?string
    {
        return $this->percentage;
    }

    public function setPercentage(?string $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }

    public function getPercentage2(): ?string
    {
        return $this->percentage2;
    }

    public function setPercentage2(?string $percentage2): self
    {
        $this->percentage2 = $percentage2;

        return $this;
    }

    public function getHitSpeed(): ?string
    {
        return $this->hitSpeed;
    }

    public function setHitSpeed(?string $hitSpeed): self
    {
        $this->hitSpeed = $hitSpeed;

        return $this;
    }

    public function getCombos(): ?int
    {
        return $this->combos;
    }

    public function setCombos(?int $combos): self
    {
        $this->combos = $combos;

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

    public function getSongDifficulty(): ?SongDifficulty
    {
        return $this->songDifficulty;
    }

    public function setSongDifficulty(?SongDifficulty $songDifficulty): self
    {
        $this->songDifficulty = $songDifficulty;

        return $this;
    }
}
