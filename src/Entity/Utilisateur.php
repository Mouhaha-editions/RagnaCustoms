<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UtilisateurRepository::class)
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 * @method string getUserIdentifier()
 */
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apiKey;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $certified;
    /**
     * @ORM\ManyToMany(targetEntity=SongRequest::class, mappedBy="mapperOnIt")
     */
    private $currentlyMapped;
    /**
     * @ORM\OneToMany(targetEntity=DownloadCounter::class, mappedBy="user")
     */
    private $downloadCounters;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;
    /**
     * @ORM\Column(type="boolean")
     */
    private $enableEmailNotification = false;
    /**
     * @ORM\OneToMany(targetEntity=Gamification::class, mappedBy="user")
     */
    private $gamifications;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isMapper = false;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isPatreon;
    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublic = false;
    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $mailingNewSong = false;
    /**
     * @ORM\Column(type="text", nullable=true)
     *
     */
    private $mapper_description = null;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_discord = null;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_img;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_name;
    /**
     * @ORM\OneToOne(targetEntity=Overlay::class, mappedBy="user", cascade={"persist", "remove"})
     */
    private $overlay;
    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;
    /**
     * @ORM\OneToMany(targetEntity=Playlist::class, mappedBy="user", orphanRemoval=true)
     */
    private $playlists;
    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];
    /**
     * @ORM\OneToMany(targetEntity=ScoreHistory::class, mappedBy="user", orphanRemoval=true)
     */
    private $scoreHistories;
    /**
     * @ORM\OneToMany(targetEntity=Score::class, mappedBy="user")
     */
    private $scores;

    /**
     * @ORM\OneToMany(targetEntity=SongRequestVote::class, mappedBy="user", orphanRemoval=true)
     */
    private $songRequestVotes;
    /**
     * @ORM\OneToMany(targetEntity=SongRequest::class, mappedBy="requestedBy", orphanRemoval=true)
     */
    private $songRequests;
    /**
     * @ORM\OneToMany(targetEntity=Song::class, mappedBy="user")
     * @ORM\OrderBy({"updatedAt"="desc"})
     */
    private $songs;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $steamCommunityId;
    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;
    /**
     * @ORM\OneToMany(targetEntity=VoteCounter::class, mappedBy="user", orphanRemoval=true)
     */
    private $voteCounter;
    /**
     * @ORM\OneToMany(targetEntity=Vote::class, mappedBy="user", orphanRemoval=true)
     */
    private $votes;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->downloadCounters = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->scoreHistories = new ArrayCollection();
        $this->gamifications = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->songRequests = new ArrayCollection();
        $this->currentlyMapped = new ArrayCollection();
        $this->songRequestVotes = new ArrayCollection();
        $this->voteCounter = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->username;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getGravatar(): ?string
    {
        return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->email))) . "?d=" . urlencode("https://ragnacustoms.com/apps/runes.png") . "&s=300";
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function addSong(Song $song): self
    {
        if (!$this->songs->contains($song)) {
            $this->songs[] = $song;
            $song->setUser($this);
        }

        return $this;
    }

    public function removeSong(Song $song): self
    {
        if ($this->songs->removeElement($song)) {
            // set the owning side to null (unless already changed)
            if ($song->getUser() === $this) {
                $song->setUser(null);
            }
        }

        return $this;
    }

    public function addVote(Vote $vote): self
    {
        if (!$this->votes->contains($vote)) {
            $this->votes[] = $vote;
            $vote->setUser($this);
        }

        return $this;
    }

    public function removeVote(Vote $vote): self
    {
        if ($this->votes->removeElement($vote)) {
            // set the owning side to null (unless already changed)
            if ($vote->getUser() === $this) {
                $vote->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Vote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function isCertified(): ?bool
    {
        return $this->certified;
    }

    public function setCertified(?bool $certified): self
    {
        $this->certified = $certified;

        return $this;
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
            $downloadCounter->setUser($this);
        }

        return $this;
    }

    public function removeDownloadCounter(DownloadCounter $downloadCounter): self
    {
        if ($this->downloadCounters->removeElement($downloadCounter)) {
            // set the owning side to null (unless already changed)
            if ($downloadCounter->getUser() === $this) {
                $downloadCounter->setUser(null);
            }
        }

        return $this;
    }

    public function getSteamCommunityId(): ?string
    {
        return $this->steamCommunityId;
    }

    public function setSteamCommunityId(?string $steamCommunityId): self
    {
        $this->steamCommunityId = $steamCommunityId;

        return $this;
    }

    public function addScore(Score $score): self
    {
        if (!$this->scores->contains($score)) {
            $this->scores[] = $score;
            $score->setUser($this);
        }

        return $this;
    }

    public function removeScore(Score $score): self
    {
        if ($this->scores->removeElement($score)) {
            // set the owning side to null (unless already changed)
            if ($score->getUser() === $this) {
                $score->setUser(null);
            }
        }

        return $this;
    }

    public function getIsMapper(): ?bool
    {
        return $this->isMapper;
    }

    public function setIsMapper(?bool $isMapper = false): self
    {
        $this->isMapper = $isMapper;

        return $this;
    }

    public function getMapperName(): ?string
    {
        return $this->mapper_name;
    }

    public function setMapperName(string $mapper_name): self
    {
        $this->mapper_name = $mapper_name;

        return $this;
    }

    public function getMapperDescription(): ?string
    {
        return $this->mapper_description;
    }

    public function setMapperDescription(?string $mapper_description): self
    {
        $this->mapper_description = $mapper_description;

        return $this;
    }

    public function getMapperImg(): ?string
    {
        return $this->mapper_img;
    }

    public function setMapperImg(?string $mapper_img): self
    {
        $this->mapper_img = $mapper_img;

        return $this;
    }

    public function getMailingNewSong(): ?bool
    {
        return $this->mailingNewSong;
    }

    public function setMailingNewSong(bool $mailingNewSong): self
    {
        $this->mailingNewSong = $mailingNewSong;

        return $this;
    }

    public function getMapperDiscord(): ?string
    {
        return $this->mapper_discord;
    }

    public function setMapperDiscord(string $mapper_discord = null): self
    {
        $this->mapper_discord = $mapper_discord;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic = false): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getEnableEmailNotification(): ?bool
    {
        return $this->enableEmailNotification;
    }

    public function setEnableEmailNotification(bool $enableEmailNotification = false): self
    {
        $this->enableEmailNotification = $enableEmailNotification;

        return $this;
    }

    public function getBestScore(Score $scoreSample, Season $season = null)
    {
        $scores = $this->getScores()->filter(function (Score $score) use ($scoreSample, $season) {
            if ($season == null) {
                return $score->getDifficulty() === $scoreSample->getDifficulty() && $scoreSample->getHash() == $score->getHash();
            }
            return $score->getDifficulty() === $scoreSample->getDifficulty() && $scoreSample->getHash() == $score->getHash() && $score->getSeason() === $season;
        });
        $max = 0;
        /** @var Score $score */
        foreach ($scores as $score) {
            if ($score->getScore() >= $max) {
                $max = $score->getScore();
            }
        }
        return $max;
    }

    /**
     * @return Collection|Score[]
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function getOverlay(): ?Overlay
    {
        return $this->overlay;
    }

    public function setOverlay(Overlay $overlay): self
    {
        // set the owning side of the relation if necessary
        if ($overlay->getUser() !== $this) {
            $overlay->setUser($this);
        }

        $this->overlay = $overlay;

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
            $scoreHistory->setUser($this);
        }

        return $this;
    }

    public function removeScoreHistory(ScoreHistory $scoreHistory): self
    {
        if ($this->scoreHistories->removeElement($scoreHistory)) {
            // set the owning side to null (unless already changed)
            if ($scoreHistory->getUser() === $this) {
                $scoreHistory->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Gamification[]
     */
    public function getGamifications(): Collection
    {
        return $this->gamifications;
    }

    public function addGamification(Gamification $gamification): self
    {
        if (!$this->gamifications->contains($gamification)) {
            $this->gamifications[] = $gamification;
            $gamification->setUser($this);
        }

        return $this;
    }

    public function removeGamification(Gamification $gamification): self
    {
        if ($this->gamifications->removeElement($gamification)) {
            // set the owning side to null (unless already changed)
            if ($gamification->getUser() === $this) {
                $gamification->setUser(null);
            }
        }

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
            $playlist->setUser($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): self
    {
        if ($this->playlists->removeElement($playlist)) {
            // set the owning side to null (unless already changed)
            if ($playlist->getUser() === $this) {
                $playlist->setUser(null);
            }
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method string getUserIdentifier()
    }

    /**
     * @return Collection|SongRequest[]
     */
    public function getSongRequests(): Collection
    {
        return $this->songRequests;
    }

    /**
     * @return ?SongRequest
     */
    public function getSongRequestInProgress(): ?SongRequest
    {
        foreach ($this->getCurrentlyMapped() as $request) {
            if ($request->getState() == SongRequest::STATE_IN_PROGRESS) {
                return $request;
            }
        }
        return null;
    }

    /**
     * @return Collection|SongRequest[]
     */
    public function getCurrentlyMapped(): Collection
    {
        return $this->currentlyMapped;
    }

    public function addSongRequest(SongRequest $songRequest): self
    {
        if (!$this->songRequests->contains($songRequest)) {
            $songRequest->setRequestedBy($this);
        }

        return $this;
    }

    public function removeSongRequest(SongRequest $songRequest): self
    {
        if ($this->songRequests->removeElement($songRequest)) {
            // set the owning side to null (unless already changed)
            if ($songRequest->getRequestedBy() === $this) {
                $songRequest->setRequestedBy(null);
            }
        }

        return $this;
    }

    public function addCurrentlyMapped(SongRequest $currentlyMapped): self
    {
        if (!$this->currentlyMapped->contains($currentlyMapped)) {
            $this->currentlyMapped[] = $currentlyMapped;
            $currentlyMapped->addMapperOnIt($this);
        }

        return $this;
    }

    public function removeCurrentlyMapped(SongRequest $currentlyMapped): self
    {
        if ($this->currentlyMapped->removeElement($currentlyMapped)) {
            $currentlyMapped->removeMapperOnIt($this);
        }

        return $this;
    }

    public function getIsPatreon(): ?bool
    {
        return $this->isPatreon;
    }

    public function setIsPatreon(?bool $isPatreon): self
    {
        $this->isPatreon = $isPatreon;

        return $this;
    }

    public function addSongRequestVote(SongRequestVote $songRequestVote): self
    {
        if (!$this->songRequestVotes->contains($songRequestVote)) {
            $this->songRequestVotes[] = $songRequestVote;
            $songRequestVote->setUser($this);
        }

        return $this;
    }

    public function removeSongRequestVote(SongRequestVote $songRequestVote): self
    {
        if ($this->songRequestVotes->removeElement($songRequestVote)) {
            // set the owning side to null (unless already changed)
            if ($songRequestVote->getUser() === $this) {
                $songRequestVote->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @param SongRequest $songRequest
     * @return bool
     */
    public function getWantedToo(SongRequest $songRequest): bool
    {
        return $this->getSongRequestVotes()->filter(function (SongRequestVote $songRequestVote) use ($songRequest) {
                return $songRequest === $songRequestVote->getSongRequest();
            })->count() > 0;
    }

    /**
     * @return Collection|SongRequestVote[]
     */
    public function getSongRequestVotes(): Collection
    {
        return $this->songRequestVotes;
    }

    public function getAvgRating()
    {
        $songsRating = [];
        foreach ($this->getSongs() as $song) {
            if (!$song->getWip() && $song->getVoteAverage() > 0) {
                $songsRating[] = $song->getVoteAverage();
            }
        }
        return count($songsRating) > 0 ? number_format(array_sum($songsRating) / count($songsRating), 2) : "No rating!";
    }

    /**
     * @return Collection|Song[]
     */
    public function getSongs(): Collection
    {
        return $this->songs->filter(function (Song $s) {
            return !$s->getIsDeleted();
        });
    }

    public function getPreferedGenre($top = 3)
    {
        $genres = [];
        foreach ($this->getSongs() as $song) {
            foreach ($song->getCategoryTags() as $categoryTag) {
                if (isset($genres[$categoryTag->getLabel()])) {
                    $genres[$categoryTag->getLabel()] += 1;
                } else {
                    $genres[$categoryTag->getLabel()] = 1;
                }
            }
        }
        arsort($genres);
        $real_genres = array_keys($genres);
        $real_genres = array_slice($real_genres, 0, $top);
        return implode(", ", $real_genres);
    }
}
