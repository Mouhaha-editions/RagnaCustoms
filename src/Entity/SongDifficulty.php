<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\SongDifficultyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    collectionOperations: [
        "get",
//        "post" => ["security" => "is_granted('ROLE_ADMIN')"],
    ],
    itemOperations: [
        "get",
//        "put" => ["security" => "is_granted('ROLE_ADMIN') or object.owner == user"],
    ],
    normalizationContext: ['groups' => ['read']])]
#[ORM\Entity(repositoryClass: SongDifficultyRepository::class)]
class SongDifficulty
{
    #[Groups("read")]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[Groups("read")]
    #[ORM\Column(type: 'float', nullable: true)]
    private $NotePerSecond;
    #[Groups("read")]
    #[ORM\Column(type: 'string', length: 255)]
    private $difficulty;
    #[Groups("read")]
    #[ORM\ManyToOne(targetEntity: DifficultyRank::class, inversedBy: 'songDifficulties')]
    private $difficultyRank;
    #[Groups("read")]
    #[ORM\Column(type: 'integer')]
    private $noteJumpMovementSpeed;
    #[Groups("read")]
    #[ORM\Column(type: 'integer')]
    private $noteJumpStartBeatOffset;
    #[Groups("read")]
    #[ORM\Column(type: 'integer', nullable: true)]
    private $notesCount;

    #[Groups("read")]
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\ManyToOne(targetEntity: Song::class, inversedBy: 'songDifficulties', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private $song;


    #[Groups("read")]
    #[ORM\Column(type: 'decimal', precision: 20, scale: 6, nullable: true)]
    private $claw_difficulty;

    #[Groups("read")]
    #[ORM\Column(type: 'float', nullable: false)]
    private $theoricalMaxScore;

    #[Groups("read")]
    #[ORM\Column(type: 'float', nullable: false)]
    private $theoricalMinScore;

    #[Groups("read")]
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $isRanked;

    #[ORM\OneToMany(targetEntity: Score::class, mappedBy: 'songDifficulty')]
    private $scores;

    #[ORM\OneToMany(targetEntity: ScoreHistory::class, mappedBy: 'songDifficulty')]
    private $scoreHistories;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $wanadevHash;

    public function __construct()
    {
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

    public function getClawDifficulty(): ?string
    {
        return $this->claw_difficulty;
    }

    public function setClawDifficulty($claw_difficulty): self
    {
        $this->claw_difficulty = $claw_difficulty;

        return $this;
    }

    public function getTheoricalMaxScore(): ?float
    {
        return $this->theoricalMaxScore;
    }

    public function setTheoricalMaxScore($theoricalMaxScore): self
    {
        $this->theoricalMaxScore = $theoricalMaxScore;

        return $this;
    }

    public function getTheoricalMinScore(): ?float
    {
        return $this->theoricalMinScore;
    }

    public function setTheoricalMinScore($theoricalMinScore): self
    {
        $this->theoricalMinScore = $theoricalMinScore;

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


    public function isRanked(): ?bool
    {
        return $this->isRanked;
    }

    public function setIsRanked(?bool $isRanked): self
    {
        $this->isRanked = $isRanked;

        return $this;
    }

    public function getWanadevHash(): ?string
    {
        return $this->wanadevHash;
    }

    public function setWanadevHash(?string $wanadevHash): self
    {
        $this->wanadevHash = $wanadevHash;

        return $this;
    }

    public function isIsRanked(): ?bool
    {
        return $this->isRanked;
    }

}
