<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SongRepository::class)
 */
class Song
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $authorName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $levelAuthorName;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $beatsPerMinute;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shuffle;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $shufflePeriod;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $previewStartTime;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $previewDuration;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $approximativeDuration;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fileName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $coverImageFileName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $environmentName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeOffset;

    /**
     * @ORM\OneToMany(targetEntity=SongDifficulty::class, mappedBy="song")
     */
    private $songDifficulties;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $version;

    /**
     * @ORM\Column(type="integer")
     */
    private $voteUp;

    /**
     * @ORM\Column(type="integer")
     */
    private $voteDown;

    /**
     * @ORM\Column(type="integer")
     */
    private $downloads;


    public function __construct()
    {
        $this->songDifficulties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSubName(): ?string
    {
        return $this->subName;
    }

    public function setSubName(string $subName): self
    {
        $this->subName = $subName;

        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): self
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getLevelAuthorName(): ?string
    {
        return $this->levelAuthorName;
    }

    public function setLevelAuthorName(string $levelAuthorName): self
    {
        $this->levelAuthorName = $levelAuthorName;

        return $this;
    }

    public function getBeatsPerMinute(): ?float
    {
        return $this->beatsPerMinute;
    }

    public function setBeatsPerMinute(float $beatsPerMinute): self
    {
        $this->beatsPerMinute = $beatsPerMinute;

        return $this;
    }

    public function getShuffle(): ?string
    {
        return $this->shuffle;
    }

    public function setShuffle(string $shuffle): self
    {
        $this->shuffle = $shuffle;

        return $this;
    }

    public function getShufflePeriod(): ?float
    {
        return $this->shufflePeriod;
    }

    public function setShufflePeriod(float $shufflePeriod): self
    {
        $this->shufflePeriod = $shufflePeriod;

        return $this;
    }

    public function getPreviewStartTime(): ?float
    {
        return $this->previewStartTime;
    }

    public function setPreviewStartTime(float $previewStartTime): self
    {
        $this->previewStartTime = $previewStartTime;

        return $this;
    }

    public function getPreviewDuration(): ?float
    {
        return $this->previewDuration;
    }

    public function setPreviewDuration(float $previewDuration): self
    {
        $this->previewDuration = $previewDuration;

        return $this;
    }

    public function getApproximativeDurationMS(): ?string
    {
        $min = floor($this->approximativeDuration/60);
        $sec = $this->approximativeDuration - $min*60;
        return $min."m ".$sec."s";
    }
    public function getApproximativeDuration(): ?int
    {
        return $this->approximativeDuration;
    }

    public function setApproximativeDuration(int $approximativeDuration): self
    {
        $this->approximativeDuration = $approximativeDuration;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }
    public function getCoverImageExtension(): ?string
    {
        $file = explode(".",$this->coverImageFileName);
        return ".".end($file);
    }


    public function getCoverImageFileName(): ?string
    {
        return $this->coverImageFileName;
    }

    public function setCoverImageFileName(string $coverImageFileName): self
    {
        $this->coverImageFileName = $coverImageFileName;

        return $this;
    }

    public function getEnvironmentName(): ?string
    {
        return $this->environmentName;
    }

    public function setEnvironmentName(string $environmentName): self
    {
        $this->environmentName = $environmentName;

        return $this;
    }

    public function getTimeOffset(): ?int
    {
        return $this->timeOffset;
    }

    public function setTimeOffset(int $timeOffset): self
    {
        $this->timeOffset = $timeOffset;

        return $this;
    }

    /**
     * @return Collection|SongDifficulty[]
     */
    public function getSongDifficulties(): Collection
    {
        return $this->songDifficulties;
    }

    public function addSongDifficulty(SongDifficulty $songDifficulty): self
    {
        if (!$this->songDifficulties->contains($songDifficulty)) {
            $this->songDifficulties[] = $songDifficulty;
            $songDifficulty->setSong($this);
        }

        return $this;
    }

    public function removeSongDifficulty(SongDifficulty $songDifficulty): self
    {
        if ($this->songDifficulties->removeElement($songDifficulty)) {
            // set the owning side to null (unless already changed)
            if ($songDifficulty->getSong() === $this) {
                $songDifficulty->setSong(null);
            }
        }

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getVoteUp(): ?int
    {
        return $this->voteUp;
    }

    public function setVoteUp(int $voteUp): self
    {
        $this->voteUp = $voteUp;

        return $this;
    }

    public function getVoteDown(): ?int
    {
        return $this->voteDown;
    }

    public function setVoteDown(int $voteDown): self
    {
        $this->voteDown = $voteDown;

        return $this;
    }

    public function getDownloads(): ?int
    {
        return $this->downloads;
    }

    public function setDownloads(int $downloads): self
    {
        $this->downloads = $downloads;

        return $this;
    }

}
