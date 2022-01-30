<?php

namespace App\Entity;

use App\Repository\SongRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=SongRepository::class)
 */
class Song
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $approximativeDuration;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $authorName;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $beatsPerMinute;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $converted;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $countVotes;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $coverImageFileName;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;
    /**
     * @ORM\OneToMany(targetEntity=DownloadCounter::class, mappedBy="song")
     */
    private $downloadCounters;
    /**
     * @ORM\Column(type="integer")
     */
    private $downloads = 0;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $environmentName;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fileName;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $infoDatFile;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isDeleted = false;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isExplicit;
    /**
     * @ORM\Column(type="datetime")
     */
    private $lastDateUpload;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $levelAuthorName;
    /**
     * @ORM\Column(type="boolean")
     */
    private $moderated = false;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $newGuid;
    /**
     * @ORM\ManyToMany(targetEntity=Playlist::class, mappedBy="songs")
     */
    private $playlists;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $previewDuration;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $previewStartTime;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shuffle;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $shufflePeriod;
    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $slug;
    /**
     * @ORM\ManyToOne(targetEntity=SongCategory::class, inversedBy="songs")
     */
    private $songCategory;
    /**
     * @ORM\OneToMany(targetEntity=SongDifficulty::class, mappedBy="song")
     */
    private $songDifficulties;

    /**
     * @ORM\OneToMany(targetEntity=SongHash::class, mappedBy="Song")
     */
    private $songHashes;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subName;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $timeOffset;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalVotes;
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="songs")
     */
    private $user;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $version;
    /**
     * @ORM\OneToMany(targetEntity=ViewCounter::class, mappedBy="song")
     */
    private $viewCounters;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $views;
    /**
     * @ORM\Column(type="integer")
     */
    private $voteDown = 0;
    /**
     * @ORM\Column(type="integer")
     */
    private $voteUp = 0;
    /**
     * @ORM\OneToMany(targetEntity=Vote::class, mappedBy="song", orphanRemoval=true)
     */
    private $votes;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $wip = false;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $youtubeLink;

    /**
     * @ORM\OneToMany(targetEntity=VoteCounter::class, mappedBy="song")
     */
    private $voteCounters;

    public function __construct()
    {
        $this->songDifficulties = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->downloadCounters = new ArrayCollection();
        $this->viewCounters = new ArrayCollection();
        $this->songHashes = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->voteCounters = new ArrayCollection();
    }

    public function isVoteCounterBy(?UserInterface $user) {
        $votes = $this->voteCounters->filter(function(VoteCounter $voteCounter)use($user){
            return $voteCounter->getUser() === $user;
        });
        return $votes->isEmpty() ? null : $votes->first();
    }

    public function isRanked()
    {
        foreach ($this->getSongDifficulties() as $difficulty) {
            foreach ($difficulty->getSeasons() as $season) {
                if ($season->isActive()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return Collection|SongDifficulty[]
     */
    public function getSongDifficulties(): Collection
    {
        return $this->songDifficulties;
    }

    public function __toString()
    {
        return $this->getName();
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
        $min = floor($this->approximativeDuration / 60);
        $sec = $this->approximativeDuration - $min * 60;
        return $min . "m " . $sec . "s";
    }

    public function getApproximativeDurationMin(): ?string
    {
        $min = floor($this->approximativeDuration / 60);
        $sec = $this->approximativeDuration - $min * 60;
        return sprintf("%d:%02d", $min, $sec);
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

    public function setVersion(string $version = null): self
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

    /**
     * @return bool
     */
    public function isModerated(): bool
    {
        return $this->moderated;
    }

    /**
     * @param bool $moderated
     */
    public function setModerated(bool $moderated): void
    {
        $this->moderated = $moderated;
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

    public function addVote(Vote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
            $vote->setSong($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->removeElement($vote)) {
            // set the owning side to null (unless already changed)
            if ($vote->getSong() === $this) {
                $vote->setSong(null);
            }
        }

        return $this;
    }

    public function getSongDifficultiesStr()
    {
        $diff = [];
        foreach ($this->getSongDifficulties() as $difficulty) {
            $diff[] = $difficulty->getDifficultyRank()->getLevel();
        }
        return join(', ', $diff);
    }

    public function getVoteAverage()
    {
        return $this->countVotes == 0 ? 0 : $this->getTotalVotes() / $this->getCountVotes();
    }

    public function getTotalVotes(): ?float
    {
        return $this->totalVotes;
    }

    public function setTotalVotes(?float $totalVotes): self
    {
        $this->totalVotes = $totalVotes;

        return $this;
    }

    public function getCountVotes(): ?int
    {
        return $this->countVotes;
    }

    public function setCountVotes(?int $countVotes): self
    {
        $this->countVotes = $countVotes;

        return $this;
    }

    public function getDescription(): ?string
    {
        return ($this->description);
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(?int $views): self
    {
        $this->views = $views;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->getLastDateUpload() >= (new DateTime())->modify('-3 days');
    }

    public function getLastDateUpload(): ?DateTimeInterface
    {
        return $this->lastDateUpload;
    }

    public function setLastDateUpload(DateTimeInterface $lastDateUpload): self
    {
        $this->lastDateUpload = $lastDateUpload;

        return $this;
    }

    public function getConverted(): ?bool
    {
        return $this->converted;
    }

    public function setConverted(?bool $converted): self
    {
        $this->converted = $converted;

        return $this;
    }

    public function getFunFactorAverage(): ?float
    {
        $sum = 0;
        $votes = $this->getVotes();
        if (count($votes) == 0) {
            return 0;
        }
        foreach ($votes as $vote) {
            $sum += $vote->getFunFactor();
        }
        return $sum / count($votes);
    }

    /**
     * @return Collection|Vote[]
     */
    public function getVotes(): Collection
    {
        $song = $this;
        return $this->votes->filter(function (Vote $vote) use ($song) {
            return $song->getLastDateUpload() <= $vote->getUpdatedAt();
        });
    }

    public function getRhythmAverage(): ?float
    {
        $sum = 0;
        $votes = $this->getVotes();
        if (count($votes) == 0) {
            return 0;
        }
        foreach ($votes as $vote) {
            $sum += $vote->getRhythm();
        }
        return $sum / count($votes);
    }

    public function getFlowAverage(): ?float
    {
        $sum = 0;
        $votes = $this->getVotes();
        if (count($votes) == 0) {
            return 0;
        }
        foreach ($votes as $vote) {
            $sum += $vote->getFlow();
        }
        return $sum / count($votes);
    }

    public function getPatternQualityAverage(): ?float
    {
        $sum = 0;
        $votes = $this->getVotes();
        if (count($votes) == 0) {
            return 0;
        }
        foreach ($votes as $vote) {
            $sum += $vote->getPatternQuality();
        }
        return $sum / count($votes);
    }

    public function getReadabilityAverage(): ?float
    {
        $sum = 0;
        $votes = $this->getVotes();
        if (count($votes) == 0) {
            return 0;
        }
        foreach ($votes as $vote) {
            $sum += $vote->getReadability();
        }
        return $sum / count($votes);
    }

    public function getLevelQualityAverage(): ?float
    {
        $sum = 0;
        $votes = $this->getVotes();
        if (count($votes) == 0) {
            return 0;
        }
        foreach ($votes as $vote) {
            $sum += $vote->getLevelQuality();
        }
        return $sum / count($votes);
    }

    public function getYoutubeLink(): ?string
    {
        return $this->youtubeLink;
    }

    public function setYoutubeLink(?string $youtubeLink): self
    {
        $this->youtubeLink = $youtubeLink;

        return $this;
    }

    public function getUniqDownloads()
    {
        return count($this->getDownloadCounters());
    }

    /**
     * @return Collection|DownloadCounter[]
     */
    public function getDownloadCounters(): Collection
    {
        return $this->downloadCounters;
    }

    public function addDownloadCounter(DownloadCounter $downloadCounter): self
    {
        if (!$this->downloadCounters->contains($downloadCounter)) {
            $this->downloadCounters[] = $downloadCounter;
            $downloadCounter->setSong($this);
        }

        return $this;
    }

    public function removeDownloadCounter(DownloadCounter $downloadCounter): self
    {
        if ($this->downloadCounters->removeElement($downloadCounter)) {
            // set the owning side to null (unless already changed)
            if ($downloadCounter->getSong() === $this) {
                $downloadCounter->setSong(null);
            }
        }

        return $this;
    }

    public function getUniqViews()
    {
        return count($this->getViewCounters());
    }

    /**
     * @return Collection|ViewCounter[]
     */
    public function getViewCounters(): Collection
    {
        return $this->viewCounters;
    }

    public function addViewCounter(ViewCounter $viewCounter): self
    {
        if (!$this->viewCounters->contains($viewCounter)) {
            $this->viewCounters[] = $viewCounter;
            $viewCounter->setSong($this);
        }

        return $this;
    }

    public function removeViewCounter(ViewCounter $viewCounter): self
    {
        if ($this->viewCounters->removeElement($viewCounter)) {
            // set the owning side to null (unless already changed)
            if ($viewCounter->getSong() === $this) {
                $viewCounter->setSong(null);
            }
        }

        return $this;
    }

    public function getInfoDatFile(): ?string
    {
        return $this->infoDatFile;
    }

    public function setInfoDatFile(?string $infoDatFile): self
    {
        $this->infoDatFile = $infoDatFile;

        return $this;
    }

    public function getNewGuid(): ?string
    {
        return $this->newGuid;
    }

    public function setNewGuid(?string $newGuid): self
    {
        $this->newGuid = $newGuid;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function addSongHash(SongHash $songHash): self
    {
        if (!$this->songHashes->contains($songHash)) {
            $this->songHashes[] = $songHash;
            $songHash->setSong($this);
        }

        return $this;
    }

    public function removeSongHash(SongHash $songHash): self
    {
        if ($this->songHashes->removeElement($songHash)) {
            // set the owning side to null (unless already changed)
            if ($songHash->getSong() === $this) {
                $songHash->setSong(null);
            }
        }

        return $this;
    }

    public function getWip(): ?bool
    {
        return $this->wip;
    }

    public function setWip(?bool $wip): self
    {
        $this->wip = $wip;

        return $this;
    }

    public function getBestRating()
    {
        $best = 0;
        foreach ($this->getVotes() as $vote) {
            if ($vote->getAverage() == 5) {
                return 5;
            }
            if ($vote->getAverage() > $best) {
                $best = $vote->getAverage();
            }
        }
        return $best;
    }

    public function getHashes()
    {
        return array_map(function (SongHash $hash) {
            return $hash->getHash();
        }, $this->getSongHashes()->toArray());
    }

    /**
     * @return Collection|SongHash[]
     */
    public function getSongHashes(): Collection
    {
        return $this->songHashes;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getSongCategory(): ?SongCategory
    {
        return $this->songCategory;
    }

    public function setSongCategory(?SongCategory $songCategory): self
    {
        $this->songCategory = $songCategory;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection|Playlist[]
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): self
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists[] = $playlist;
            $playlist->addSong($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): self
    {
        if ($this->playlists->removeElement($playlist)) {
            $playlist->removeSong($this);
        }

        return $this;
    }

    public function getIsExplicit(): ?bool
    {
        return $this->isExplicit;
    }

    public function setIsExplicit(?bool $isExplicit): self
    {
        $this->isExplicit = $isExplicit;

        return $this;
    }
    public function hasCover():bool
    {
        $cover = "/covers/" . $this->getId() . $this->getCoverImageExtension();
        if (!file_exists(__DIR__ . "/../../public/" . $cover)) {
           return false;
        }
        return true;
    }
    public function getCover()
    {
        $cover = "/covers/" . $this->getId() . $this->getCoverImageExtension();
        if (!file_exists(__DIR__ . "/../../public/" . $cover)) {
            $cover = $this->getPlaceholder();
        }
        return $cover;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoverImageExtension(): ?string
    {
        $file = explode(".", $this->coverImageFileName);
        return "." . end($file);
    }

    public function getPlaceholder()
    {
        return "/apps/logo.png";
    }
    /**
     * @return Collection|VoteCounter[]
     */
    public function getVoteCounters(): Collection
    {
        return $this->voteCounters;
    }

    public function addVoteCounter(VoteCounter $voteCounter): self
    {
        if (!$this->voteCounters->contains($voteCounter)) {
            $this->voteCounters[] = $voteCounter;
            $voteCounter->setSong($this);
        }

        return $this;
    }

    public function removeVoteCounter(VoteCounter $voteCounter): self
    {
        if ($this->voteCounters->removeElement($voteCounter)) {
            // set the owning side to null (unless already changed)
            if ($voteCounter->getSong() === $this) {
                $voteCounter->setSong(null);
            }
        }

        return $this;
    }
}
