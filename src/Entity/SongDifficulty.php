<?php

namespace App\Entity;

use App\Repository\SongDifficultyRepository;
use ContainerQGkBoxD\getUtilisateur2Service;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SongDifficultyRepository::class)
 */
class SongDifficulty
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $NotePerSecond;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $difficulty;
    /**
     * @ORM\ManyToOne(targetEntity=DifficultyRank::class, inversedBy="songDifficulties")
     */
    private $difficultyRank;
    /**
     * @ORM\Column(type="integer")
     */
    private $noteJumpMovementSpeed;
    /**
     * @ORM\Column(type="integer")
     */
    private $noteJumpStartBeatOffset;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $notesCount;
    /**
     * @ORM\ManyToMany(targetEntity=Season::class, mappedBy="difficulties")
     */
    private $seasons;
    /**
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="songDifficulties",cascade={"persist", "remove"})
     */
    private $song;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $claw_difficulty;

    /**
     * @ORM\OneToMany(targetEntity=Score::class, mappedBy="SongDifficulty")
     */
    private $scores;

    /**
     * @ORM\OneToMany(targetEntity=ScoreHistory::class, mappedBy="SongDifficulty")
     */
    private $scoreHistories;

    public function __construct()
    {
        $this->seasons = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->scoreHistories = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getSong()->getName() . " Level " . $this->getDifficultyRank()->getLevel();
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

    public function getDifficultyRank(): ?DifficultyRank
    {
        return $this->difficultyRank;
    }

    public function setDifficultyRank(?DifficultyRank $difficultyRank): self
    {
        $this->difficultyRank = $difficultyRank;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNoteJumpMovementSpeed(): ?int
    {
        return $this->noteJumpMovementSpeed;
    }

    public function setNoteJumpMovementSpeed(int $noteJumpMovementSpeed): self
    {
        $this->noteJumpMovementSpeed = $noteJumpMovementSpeed;

        return $this;
    }

    public function getNoteJumpStartBeatOffset(): ?int
    {
        return $this->noteJumpStartBeatOffset;
    }

    public function setNoteJumpStartBeatOffset(int $noteJumpStartBeatOffset): self
    {
        $this->noteJumpStartBeatOffset = $noteJumpStartBeatOffset;

        return $this;
    }

    public function getNotesCount(): ?int
    {
        return $this->notesCount;
    }

    public function setNotesCount(?int $notesCount): self
    {
        $this->notesCount = $notesCount;

        return $this;
    }

    public function getNotePerSecond(): ?float
    {
        return $this->NotePerSecond;
    }

    public function setNotePerSecond(?float $NotePerSecond): self
    {
        $this->NotePerSecond = $NotePerSecond;

        return $this;
    }

    public function addSeason(Season $season): self
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons[] = $season;
            $season->addDifficulty($this);
        }

        return $this;
    }

    public function removeSeason(Season $season): self
    {
        if ($this->seasons->removeElement($season)) {
            $season->removeDifficulty($this);
        }

        return $this;
    }

    /**
     * @param Utilisateur $user
     * @param Season|null $season
     * @return bool
     */
    public function isPlayedBy(Utilisateur $user, Season $season = null)
    {
        if ($this->isRanked() && $season != null) {
            foreach ($user->getScores() as $score) {
                if ($score->getSeason() != null && in_array($score->getHash(), $this->getSong()->getHashes()) && $score->getDifficulty() == $this->getDifficultyRank()->getLevel() && $score->getSeason()->getId() === $season->getId() && $score->getUser() === $user) {
                    return true;
                }
            }
        }else{
            foreach ($user->getScores() as $score) {
                 $hashes = $this->getSong()->getHashes();
                 $userId = $score->getUser()->getId();
                 $level = $this->getDifficultyRank()->getLevel();

                if (in_array($score->getHash(), $hashes) && $score->getDifficulty() == $level &&  $userId === $user->getId()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function isRanked()
    {
        foreach ($this->getSeasons() as $season) {
            if ($season->isActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection|Season[]
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function getClawDifficulty(): ?string
    {
        return $this->claw_difficulty;
    }

    public function setClawDifficulty($claw_difficulty): self
    {
        $this->claw_difficulty = $claw_difficulty;

        return $this;
    }

    /**
     * @return Collection|Score[]
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(Score $score): self
    {
        if (!$this->scores->contains($score)) {
            $this->scores[] = $score;
            $score->setSongDifficulty($this);
        }

        return $this;
    }

    public function removeScore(Score $score): self
    {
        if ($this->scores->removeElement($score)) {
            // set the owning side to null (unless already changed)
            if ($score->getSongDifficulty() === $this) {
                $score->setSongDifficulty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ScoreHistory[]
     */
    public function getScoreHistories(): Collection
    {
        return $this->scoreHistories;
    }

    public function addScoreHistory(ScoreHistory $scoreHistory): self
    {
        if (!$this->scoreHistories->contains($scoreHistory)) {
            $this->scoreHistories[] = $scoreHistory;
            $scoreHistory->setSongDifficulty($this);
        }

        return $this;
    }

    public function removeScoreHistory(ScoreHistory $scoreHistory): self
    {
        if ($this->scoreHistories->removeElement($scoreHistory)) {
            // set the owning side to null (unless already changed)
            if ($scoreHistory->getSongDifficulty() === $this) {
                $scoreHistory->setSongDifficulty(null);
            }
        }

        return $this;
    }

}
