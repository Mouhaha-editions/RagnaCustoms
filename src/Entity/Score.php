<?php

namespace App\Entity;

use App\Repository\ScoreRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScoreRepository::class)
 * @ORM\Table(name="score", uniqueConstraints={
 *  @ORM\UniqueConstraint(name="user_difficulty",
 *            columns={"user_id", "season_id","hash","difficulty"})
 *     })
 */
class Score
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $comboBlue;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $comboYellow;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hit;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hitDeltaAverage;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hitPercentage;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $missed;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $percentageOfPerfects;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra;

    /**
     * @deprecated
     * @ORM\Column(type="integer", nullable=true)
     */
    private $combos;
    /**
     * @deprecated
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $difficulty;
    /**
     * @deprecated
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hash;
    /**
     * @deprecated
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $hitAccuracy;
    /**
     * @deprecated
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $hitSpeed;
    /**
     * @deprecated
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesHit;
    /**
     * @deprecated
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesMissed;
    /**
     * @deprecated
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesNotProcessed;
    /**
     * @deprecated
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $percentage;
    /**
     * @deprecated
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $percentage2;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $rawPP;
    /**
     * @ORM\Column(type="float")
     */
    private $score;
    /**
     * @ORM\ManyToOne(targetEntity=Season::class, inversedBy="scores")
     */
    private $season;
    /**
     * @deprecated
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="scores")
     * @ORM\Column(nullable=true)
     */
    private $song;
    /**
     * @ORM\ManyToOne(targetEntity=SongDifficulty::class, inversedBy="scores")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $songDifficulty;
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="scores")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

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

    public function getScoreDisplay(): ?string
    {
        return $this->score/100;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    /**
     * @return Season|null
     * @deprecated
     */
    public function getSeason(): ?Season
    {
        return $this->season;
    }

    /**
     * @param Season|null $season
     * @return Score
     * @deprecated
     */
    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    /**
     * @param string|null $difficulty
     * @return $this
     * @deprecated
     */
    public function setDifficulty(?string $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     * @return Score
     * @deprecated
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getNotesHit(): ?int
    {
        return $this->notesHit;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setNotesHit(?int $notesHit): self
    {
        $this->notesHit = $notesHit;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getNotesMissed(): ?int
    {
        return $this->notesMissed;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setNotesMissed(?int $notesMissed): self
    {
        $this->notesMissed = $notesMissed;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getNotesNotProcessed(): ?int
    {
        return $this->notesNotProcessed;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setNotesNotProcessed(?int $notesNotProcessed): self
    {
        $this->notesNotProcessed = $notesNotProcessed;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getHitAccuracy(): ?string
    {
        return $this->hitAccuracy;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setHitAccuracy(?string $hitAccuracy): self
    {
        $this->hitAccuracy = $hitAccuracy;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getPercentage(): ?string
    {
        return $this->percentage;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setPercentage(?string $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getPercentage2(): ?string
    {
        return $this->percentage2;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setPercentage2(?string $percentage2): self
    {
        $this->percentage2 = $percentage2;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getHitSpeed(): ?string
    {
        return $this->hitSpeed;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setHitSpeed(?string $hitSpeed): self
    {
        $this->hitSpeed = $hitSpeed;

        return $this;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function getCombos(): ?int
    {
        return $this->combos;
    }

    /**
     * @return string|null
     * @deprecated
     */
    public function setCombos(?int $combos): self
    {
        $this->combos = $combos;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getRawPP(): ?float
    {
        return $this->rawPP;
    }

    /**
     * @param float|null $rawPP
     * @return $this
     */
    public function setRawPP(?float $rawPP): self
    {
        $this->rawPP = $rawPP;

        return $this;
    }

    /**
     * @return Song|null
     * @deprecated
     */
    public function getSong(): ?Song
    {
        return $this->song;
    }

    /**
     * @param Song|null $song
     * @return Score
     * @deprecated
     */
    public function setSong(?Song $song): self
    {
        $this->song = $song;

        return $this;
    }

    /**
     * @return SongDifficulty|null
     */
    public function getSongDifficulty(): ?SongDifficulty
    {
        return $this->songDifficulty;
    }

    /**
     * @param SongDifficulty|null $SongDifficulty
     * @return $this
     */
    public function setSongDifficulty(?SongDifficulty $SongDifficulty): self
    {
        $this->songDifficulty = $SongDifficulty;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPercentageOfPerfects()
    {
        return $this->percentageOfPerfects;
    }

    /**
     * @param mixed $percentageOfPerfects
     */
    public function setPercentageOfPerfects($percentageOfPerfects): void
    {
        $this->percentageOfPerfects = $percentageOfPerfects;
    }

    /**
     * @return mixed
     */
    public function getComboBlue()
    {
        return $this->comboBlue;
    }

    /**
     * @param mixed $comboBlue
     */
    public function setComboBlue($comboBlue): void
    {
        $this->comboBlue = $comboBlue;
    }

    /**
     * @return mixed
     */
    public function getComboYellow()
    {
        return $this->comboYellow;
    }

    /**
     * @param mixed $comboYellow
     */
    public function setComboYellow($comboYellow): void
    {
        $this->comboYellow = $comboYellow;
    }

    /**
     * @return mixed
     */
    public function getMissed()
    {
        return $this->missed;
    }

    /**
     * @param mixed $missed
     */
    public function setMissed($missed): void
    {
        $this->missed = $missed;
    }

    /**
     * @return mixed
     */
    public function getHit()
    {
        return $this->hit;
    }

    /**
     * @param mixed $hit
     */
    public function setHit($hit): void
    {
        $this->hit = $hit;
    }

    /**
     * @return mixed
     */
    public function getHitPercentage()
    {
        return $this->hitPercentage;
    }

    /**
     * @param mixed $hitPercentage
     */
    public function setHitPercentage($hitPercentage): void
    {
        $this->hitPercentage = $hitPercentage;
    }

    /**
     * @return mixed
     */
    public function getHitDeltaAverage()
    {
        return $this->hitDeltaAverage;
    }

    /**
     * @param mixed $hitDeltaAverage
     */
    public function setHitDeltaAverage($hitDeltaAverage): void
    {
        $this->hitDeltaAverage = $hitDeltaAverage;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $extra
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }

}
