<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\SongRepository;
use App\Service\StatisticService;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=SongRepository::class)
 */
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
class Song
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups("read")]
    private $id;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[Groups("read")]
    private $active;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    #[Groups("read")]
    private $approximativeDuration;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups("read")]
    private $authorName;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups("read")]
    private $beatsPerMinute;
    /**
     * @ORM\ManyToMany(targetEntity=SongCategory::class, inversedBy="songs")
     */
    #[Groups("read")]
    private $categoryTags;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[Groups("read")]
    private $converted;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    #[Groups("read")]
    private $countVotes;
    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups("read")]
    private $coverImageFileName;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups("read")]
    private $description;
    /**
     * @ORM\OneToMany(targetEntity=DownloadCounter::class, mappedBy="song")
     */
    private $downloadCounters;
    /**
     * @ORM\Column(type="integer")
     */
    #[Groups("read")]
    private $downloads = 0;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups("read")]
    private $environmentName;
    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups("read")]
    private $fileName;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups("read")]
    private $infoDatFile;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[Groups("read")]
    private $isDeleted = false;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[Groups("read")]
    private $isExplicit;
    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups("read")]
    private $lastDateUpload;
    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups("read")]
    private $levelAuthorName;
    /**
     * @ORM\Column(type="boolean")
     */
    #[Groups("read")]
    private $moderated = false;
    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups("read")]
    private $name;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups("read")]
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
    #[Groups("read")]
    private $slug;
    /**
     * @ORM\OneToMany(targetEntity=SongDifficulty::class, mappedBy="song")
     */
    #[Groups("read")]
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

    #[Groups("read")]
    private $user;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $version;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $views;
    /**
     * @ORM\OneToMany(targetEntity=VoteCounter::class, mappedBy="song")
     */
    private $voteCounters;
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
    #[Groups("read")]
    private $wip = false;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups("read")]
    private $youtubeLink;

    public function __construct()
    {
        $this->songDifficulties = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->downloadCounters = new ArrayCollection();
        $this->songHashes = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->voteCounters = new ArrayCollection();
        $this->categoryTags = new ArrayCollection();
    }

    public function isVoteCounterBy(?UserInterface $user)
    {
        $votes = $this->voteCounters->filter(function (VoteCounter $voteCounter) use ($user) {
            return $voteCounter->getUser() === $user;
        });
        return $votes->isEmpty() ? null : $votes->first();
    }

    public function isReviewedBy(?UserInterface $user): bool
    {
        $votes = $this->votes->filter(function (Vote $vote) use ($user) {
            return $vote->getUser() === $user;
        });
        return count($votes) > 0;
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
        return $this->getName().($this->getConverted() == true ? " <small data-toggle='tooltip' title='Converted' class='badge badge-danger'>C</small>":"").
            ($this->getIsExplicit() ?" <small data-toggle='tooltip' title='Explicit content' class='badge badge-warning'>E</small>":"");
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

    public function getMapper()
    {
        return $this->user->getMapperName() ?? $this->user->getUsername();
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

    public function getSongDifficultiesStr(): string
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

    public function isRanked(): bool
    {
        foreach ($this->getSongDifficulties() as $diff) {
            if ($diff->isRanked()) {
                return true;
            }
        }
        return false;
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

    public function getUniqDownloads(): int
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

    public function getHashes(): array
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

    public function hasCover(): bool
    {
        $cover = "/covers/" . $this->getId() . $this->getCoverImageExtension();
        if (!file_exists(__DIR__ . "/../../public/" . $cover)) {
            return false;
        }
        return true;
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

    public function getCover(): string
    {
        $cover = "/covers/" . $this->getId() . $this->getCoverImageExtension();
        if (!file_exists(__DIR__ . "/../../public/" . $cover)) {
            $cover = $this->getPlaceholder();
        }
        return $cover . "?t=" . date('dmYH');
    }

    public function getPlaceholder(): string
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

    /**
     * @return Collection|SongCategory[]
     */
    public function getCategoryTags(): Collection
    {
        return $this->categoryTags;
    }

    /**
     * @return string[]
     */
    public function getSongCategoryTags(): array
    {
        $result = [];
        /** @var SongCategory $cat */
        foreach ($this->categoryTags as $cat) {
            $result[] = $cat->getLabel();
        }

        return count($result) > 0 ? $result : ["none"];
    }

    public function addCategoryTag(SongCategory $categoryTag): self
    {
        if (!$this->categoryTags->contains($categoryTag)) {
            $this->categoryTags[] = $categoryTag;
        }

        return $this;
    }

    public function removeCategoryTag(SongCategory $categoryTag): self
    {
        $this->categoryTags->removeElement($categoryTag);

        return $this;
    }

    /**
     * @return string
     */
    public function getTimeAgo(): string
    {
        return StatisticService::dateDiplayer($this->getLastDateUpload());
    }

    public function __api()
    {
        return [
            "Id" => $this->getId(),
            "Name" => $this->getName(),
            "IsRanked" => $this->isRanked(),
            "Hash" => $this->getNewGuid(),
            "Ragnabeat" => $this->getInfoDatFile(),
            "Author" => $this->getAuthorName(),
            "Mapper" => $this->getLevelAuthorName(),
            "Difficulties" => $this->getSongDifficultiesStr(),
            "CoverImageExtension" => $this->getCoverImageExtension(),
        ];
    }

}
