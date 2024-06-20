<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\EEmail;
use App\Enum\ENotification;
use App\Repository\UtilisateurRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [],
    denormalizationContext: ['groups' => ['user:read']],
)]
#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'Username already used')]
#[UniqueEntity(fields: ['email'], message: 'Email already used')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read'])]
    private ?int $id;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $emailPreference = null;
    #[ORM\Column(type: 'text', nullable: true)]
    private $NotificationPreference;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $apiKey;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $certified;
    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'utilisateurs')]
    private $country;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $credits;
    #[ORM\ManyToMany(targetEntity: SongRequest::class, mappedBy: 'mapperOnIt')]
    private $currentlyMapped;
    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    private $discordEmail;
    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    private $discordId;
    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    private $discordUsername;
    #[ORM\OneToMany(targetEntity: DownloadCounter::class, mappedBy: 'user')]
    private $downloadCounters;
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email(mode: 'strict')]
    private $email;
    #[ORM\Column(type: 'boolean')]
    private $enableEmailNotification = false;
    #[ORM\OneToMany(targetEntity: FollowMapper::class, mappedBy: 'user')]
    private $followedMappers;
    #[ORM\OneToMany(targetEntity: FollowMapper::class, mappedBy: 'mapper', orphanRemoval: true)]
    private $followers;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isMapper = false;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isPatreon;
    #[ORM\Column(type: 'boolean')]
    private $isPublic = true;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $mailingNewSong = false;
    #[ORM\Column(type: 'text', nullable: true)]
    private $mapper_description = null;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mapper_discord = null;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mapper_img;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['song:get'])]
    private $mapper_name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['song:get'])]
    private $usernameColor;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private $notifications;
    #[ORM\OneToOne(targetEntity: Overlay::class, mappedBy: 'user', cascade: [
        'persist',
        'remove',
    ])]
    private $overlay;
    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private $password;
    #[ORM\OneToMany(targetEntity: Playlist::class, mappedBy: 'user', orphanRemoval: true)]
    private $playlists;
    #[ORM\Column(type: 'json')]
    private $roles = [];
    #[ORM\OneToMany(targetEntity: ScoreHistory::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['updatedAt' => 'desc'])]
    private $scoreHistories;

    #[ORM\OneToMany(targetEntity: Score::class, mappedBy: 'user')]
    private $scores;

    #[ORM\OneToMany(targetEntity: RankedScores::class, mappedBy: 'user')]
    private Collection $rankedScores;

    #[ORM\OneToMany(targetEntity: SongRequestVote::class, mappedBy: 'user', orphanRemoval: true)]
    private $songRequestVotes;
    #[ORM\OneToMany(targetEntity: SongRequest::class, mappedBy: 'requestedBy', orphanRemoval: true)]
    private $songRequests;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $steamCommunityId;
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read'])]
    private $username;
    #[ORM\OneToMany(targetEntity: VoteCounter::class, mappedBy: 'user', orphanRemoval: true)]
    private $voteCounter;
    #[ORM\OneToMany(targetEntity: Vote::class, mappedBy: 'user', orphanRemoval: true)]
    private $votes;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $patreonAccessToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $patreonRefreshToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $patreonUser;

    #[ORM\Column(type: 'text', nullable: true)]
    private $patreonData;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $twitchAccessToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $twitchRefreshToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $twitchUser;

    #[ORM\Column(type: 'text', nullable: true)]
    private $twitchData;

    #[ORM\Column(nullable: true)]
    private ?int $countApiAttempt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastApiAttempt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $auth_token = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $auth_token_refresh = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CustomEvent::class, orphanRemoval: true)]
    private Collection $customEvents;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CustomEventParticipation::class, orphanRemoval: true)]
    private Collection $customEventParticipations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Friend::class, orphanRemoval: true)]
    private Collection $friendRequests;

    #[ORM\OneToMany(mappedBy: 'friend', targetEntity: Friend::class, orphanRemoval: true)]
    private Collection $friends;

    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'mappers')]
    #[ORM\OrderBy(['updatedAt' => 'desc'])]
    private Collection $songsMapped;

    #[ORM\Column]
    private ?bool $avatarDisabled = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $avatar = null;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->downloadCounters = new ArrayCollection();
        $this->scores = new ArrayCollection();
        $this->rankedScores = new ArrayCollection();
        $this->scoreHistories = new ArrayCollection();
        $this->playlists = new ArrayCollection();
        $this->songRequests = new ArrayCollection();
        $this->currentlyMapped = new ArrayCollection();
        $this->songRequestVotes = new ArrayCollection();
        $this->voteCounter = new ArrayCollection();
        $this->followedMappers = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->customEvents = new ArrayCollection();
        $this->customEventParticipations = new ArrayCollection();
        $this->friendRequests = new ArrayCollection();
        $this->friends = new ArrayCollection();
        $this->songsMapped = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->username;
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
    public function eraseCredentials(): void
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
        $size = 600;
        $defaultAvatar = 'https://ragnacustoms.com/apps/runes.png';

        if ($this->isAvatarDisabled()) {
            return $defaultAvatar;
        }

        if ($this->avatar) {
            return $this->avatar;
        }

        $urlComp = md5(strtolower(trim($this->email)));

        return 'https://www.gravatar.com/avatar/'.$urlComp.'?d='.urlencode($defaultAvatar).'&s='.$size;
    }

    public function isAvatarDisabled(): ?bool
    {
        return $this->avatarDisabled;
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

    public function isMailingNewSong(): ?bool
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

    public function setIsPublic(bool $isPublic = true): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getEnableEmailNotification(): ?bool
    {
        return $this->enableEmailNotification;
    }

    public function isEnableEmailNotification(): ?bool
    {
        return $this->enableEmailNotification;
    }

    public function setEnableEmailNotification(bool $enableEmailNotification = false): self
    {
        $this->enableEmailNotification = $enableEmailNotification;

        return $this;
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
     * @return Collection|SongRequest[]
     */
    public function getOpenSongRequests(): Collection
    {
        return $this->songRequests->filter(function (Songrequest $songrequest) {
            return $songrequest->getState() == SongRequest::STATE_ASKED;
        });
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
     *
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
        foreach ($this->getSongsMapped() as $song) {
            if (!$song->getWip() && $song->getVoteAverage() > 0) {
                $songsRating[] = $song->getVoteAverage();
            }
        }

        return count($songsRating) > 0 ? number_format(array_sum($songsRating) / count($songsRating), 2) : "No rating!";
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongsMapped(): Collection
    {
        return $this->songsMapped;
    }

    /**
     * @return Collection|Song[]
     */
    public function getSongs(): Collection
    {
        return $this->songsMapped->filter(function (Song $s) {
            return !$s->getIsDeleted();
        });
    }

    /**
     * @return Collection|Song[]
     */
    public function getSongsAvailable(): Collection
    {
        return $this->songsMapped->filter(function (Song $song) {
            return $song->isAvailable();
        });
    }

    public function getPreferedGenre($top = 3)
    {
        $genres = [];
        foreach ($this->getSongsMapped() as $song) {
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

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(?int $credits): self
    {
        $this->credits = $credits;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getLastScoreHistory()
    {
        return $this->getScoreHistories()->first();
    }

    /**
     * @return Collection|ScoreHistory[]
     */
    public function getScoreHistories(): Collection
    {
        return $this->scoreHistories;
    }

    public function getScoresRanked()
    {
        foreach ($this->getScores() as $score) {
            if ($score->getSongDifficulty()->isRanked()) {
                yield $score;
            }
        }
    }

    /**
     * @return Collection|Score[]
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function getAvgPerfect(?int $lvl = null): ?int
    {
        $scores = $this->getScores();
        $sum = 0;

        if ($lvl) {
            $scores = $scores->filter(function (Score $score) use ($lvl) {
                return $score->getSongDifficulty()->getDifficultyRank()->getId() == $lvl;
            });
        }

        if ($scores->count() === 0) {
            return null;
        }

        foreach ($scores as $score) {
            $sum += $score->getPercentageOfPerfects();
        }

        return (int)($sum / $scores->count());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPPFlat()
    {
        $scores = $this->rankedScores->filter(function (RankedScores $r) {
            return $r->getPlateform() == 'flat';
        });

        if ($scores->count() == 0) {
            return null;
        }

        return $scores->first()->getTotalPPScore();
    }

    public function getPPFlatOkod()
    {
        $scores = $this->rankedScores->filter(function (RankedScores $r) {
            return $r->getPlateform() == 'flat_okod';
        });

        if ($scores->count() == 0) {
            return null;
        }

        return $scores->first()->getTotalPPScore();
    }

    public function getPPVR()
    {
        $scores = $this->rankedScores->filter(function (RankedScores $r) {
            return $r->getPlateform() == 'vr';
        });

        if ($scores->count() == 0) {
            return null;
        }

        return $scores->first()->getTotalPPScore();
    }

    public function hasPlayed(SongDifficulty $difficulty)
    {
        foreach ($this->getScoreHistories() as $scoreHistory) {
            if ($scoreHistory->getSongDifficulty() === $difficulty) {
                return true;
            }
        }

        return false;
    }

    public function playedPlateform(SongDifficulty $difficulty, bool $vr = true)
    {
        foreach ($this->getScores() as $score) {
            if ($score->getSongDifficulty() === $difficulty && $score->isVR() == $vr) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getDiscordUsername()
    {
        return $this->discordUsername;
    }

    /**
     * @param mixed $discordUsername
     */
    public function setDiscordUsername($discordUsername): void
    {
        $this->discordUsername = $discordUsername;
    }

    /**
     * @return mixed
     */
    public function getDiscordId()
    {
        return $this->discordId;
    }

    /**
     * @param mixed $discordId
     */
    public function setDiscordId($discordId): void
    {
        $this->discordId = $discordId;
    }

    /**
     * @return mixed
     */
    public function getDiscordEmail()
    {
        return $this->discordEmail;
    }

    /**
     * @param mixed $discordEmail
     */
    public function setDiscordEmail($discordEmail): void
    {
        $this->discordEmail = $discordEmail;
    }

    public function addFollowedMapper(FollowMapper $followedMapper): self
    {
        if (!$this->followedMappers->contains($followedMapper)) {
            $this->followedMappers[] = $followedMapper;
            $followedMapper->setUser($this);
        }

        return $this;
    }

    public function removeFollowedMapper(FollowMapper $followedMapper): self
    {
        if ($this->followedMappers->removeElement($followedMapper)) {
            // set the owning side to null (unless already changed)
            if ($followedMapper->getUser() === $this) {
                $followedMapper->setUser(null);
            }
        }

        return $this;
    }

    public function getFollowersCounter()
    {
        $followers = $this->getFollowers()->count();
        if ($followers < 1000) {
            // Anything less than a million
            $n_format = number_format($followers);
        } else {
            if ($followers < 1000000 / 1000) {
                // Anything less than a million
                $n_format = number_format($followers, 1).'K';
            } else {
                if ($followers < 1000000000) {
                    // Anything less than a billion
                    $n_format = number_format($followers / 1000000, 1).'M';
                } else {
                    // At least a billion
                    $n_format = number_format($followers / 1000000000, 1).'B';
                }
            }
        }

        return $n_format." follower".($n_format > 1 ? "s" : "");
    }

    /**
     * @return Collection<int, FollowMapper>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(FollowMapper $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers[] = $follower;
            $follower->setMapper($this);
        }

        return $this;
    }

    public function removeFollower(FollowMapper $follower): self
    {
        if ($this->followers->removeElement($follower)) {
            // set the owning side to null (unless already changed)
            if ($follower->getMapper() === $this) {
                $follower->setMapper(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
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

    public function isFollower(Utilisateur $mapper)
    {
        return $this->getFollowedMappers()->filter(function (FollowMapper $follow) use ($mapper) {
            return $follow->getMapper() === $mapper;
        })->first();
    }

    /**
     * @return Collection<int, FollowMapper>
     */
    public function getFollowedMappers(): Collection
    {
        return $this->followedMappers;
    }

    public function getFollowersNotifiable(ENotification $event)
    {
        return $this->getFollowers()->filter(function (FollowMapper $follow) use ($event) {
            return $follow->getIsNotificationEnabled() && $follow->getUser()->hasNotificationPreference($event);
        });
    }

    public function hasNotificationPreference(ENotification $event)
    {
        return in_array($event, $this->getNotificationPreferences());
    }

    public function getNotificationPreferences()
    {
        return $this->getNotificationPreference() == null ? ENotification::cases() : unserialize(
            $this->getNotificationPreference()
        );
    }

    public function getNotificationPreference(): ?string
    {
        return $this->NotificationPreference;
    }

    public function setNotificationPreference(string $NotificationPreference): self
    {
        $this->NotificationPreference = $NotificationPreference;

        return $this;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    public function getUnreadNotifications()
    {
        return $this->getNotifications()->filter(function (Notification $notification) {
            return $notification->getState() == Notification::STATE_UNREAD;
        });
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function hasEmailPreference(ENotification $event): bool
    {
        return in_array($event, $this->getEmailPreferences());
    }

    public function getEmailPreferences()
    {
        return $this->getEmailPreference() == null ? EEmail::cases() : unserialize($this->getEmailPreference());
    }

    public function getEmailPreference(): ?string
    {
        return $this->emailPreference;
    }

    public function setEmailPreference(?string $emailPreference): self
    {
        $this->emailPreference = $emailPreference;

        return $this;
    }

    public function getPatreonAccessToken(): ?string
    {
        return $this->patreonAccessToken;
    }

    public function setPatreonAccessToken(?string $patreonAccessToken): self
    {
        $this->patreonAccessToken = $patreonAccessToken;

        return $this;
    }

    public function getPatreonRefreshToken(): ?string
    {
        return $this->patreonRefreshToken;
    }

    public function setPatreonRefreshToken(?string $patreonRefreshToken): self
    {
        $this->patreonRefreshToken = $patreonRefreshToken;

        return $this;
    }

    public function getPatreonUser(): ?string
    {
        return $this->patreonUser;
    }

    public function setPatreonUser(?string $patreonUser): self
    {
        $this->patreonUser = $patreonUser;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPatreonData()
    {
        return $this->patreonData;
    }

    /**
     * @param mixed $patreonData
     */
    public function setPatreonData($patreonData): self
    {
        $this->patreonData = $patreonData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsernameColor()
    {
        return $this->usernameColor ?? "#0b8dea";
    }

    /**
     * @param mixed $usernameColor
     *
     * @return Utilisateur
     */
    public function setUsernameColor($usernameColor)
    {
        $this->usernameColor = $usernameColor;

        return $this;
    }

    public function addRole(string $string)
    {
        $this->roles[] = $string;
        $this->roles = array_unique($this->roles);

        return $this;
    }

    public function removeRole(string $string)
    {
        foreach ($this->roles as $k => $v) {
            if ($v == $string) {
                unset($this->roles[$k]);
                break;
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTwitchAccessToken()
    {
        return $this->twitchAccessToken;
    }

    /**
     * @param mixed $twitchAccessToken
     *
     * @return Utilisateur
     */
    public function setTwitchAccessToken($twitchAccessToken)
    {
        $this->twitchAccessToken = $twitchAccessToken;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTwitchRefreshToken()
    {
        return $this->twitchRefreshToken;
    }

    /**
     * @param mixed $twitchRefreshToken
     *
     * @return Utilisateur
     */
    public function setTwitchRefreshToken($twitchRefreshToken)
    {
        $this->twitchRefreshToken = $twitchRefreshToken;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTwitchUser()
    {
        return $this->twitchUser;
    }

    /**
     * @param mixed $twitchUser
     *
     * @return Utilisateur
     */
    public function setTwitchUser($twitchUser)
    {
        $this->twitchUser = $twitchUser;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTwitchData()
    {
        return $this->twitchData;
    }

    /**
     * @param mixed $twitchData
     *
     * @return Utilisateur
     */
    public function setTwitchData($twitchData)
    {
        $this->twitchData = $twitchData;

        return $this;
    }

    public function isIsMapper(): ?bool
    {
        return $this->isMapper;
    }

    public function isIsPatreon(): ?bool
    {
        return $this->isPatreon;
    }

    public function isIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    /**
     * @return Collection<int, VoteCounter>
     */
    public function getVoteCounter(): Collection
    {
        return $this->voteCounter;
    }

    public function addVoteCounter(VoteCounter $voteCounter): self
    {
        if (!$this->voteCounter->contains($voteCounter)) {
            $this->voteCounter->add($voteCounter);
            $voteCounter->setUser($this);
        }

        return $this;
    }

    public function removeVoteCounter(VoteCounter $voteCounter): self
    {
        if ($this->voteCounter->removeElement($voteCounter)) {
            // set the owning side to null (unless already changed)
            if ($voteCounter->getUser() === $this) {
                $voteCounter->setUser(null);
            }
        }

        return $this;
    }

    public function getCountApiAttempt(): ?int
    {
        return $this->countApiAttempt;
    }

    public function setCountApiAttempt(?int $countApiAttempt): self
    {
        $this->countApiAttempt = $countApiAttempt;

        return $this;
    }

    public function getLastApiAttempt(): ?DateTimeInterface
    {
        return $this->lastApiAttempt;
    }

    public function setLastApiAttempt(?DateTimeInterface $lastApiAttempt): self
    {
        $this->lastApiAttempt = $lastApiAttempt;

        return $this;
    }

    public function getRankedSong()
    {
        return $this->getSongsMapped()->filter(function (Song $song) {
            return $song->isRanked();
        });
    }

    public function getAuthToken(): ?string
    {
        return $this->auth_token;
    }

    public function setAuthToken(?string $auth_token): static
    {
        $this->auth_token = $auth_token;

        return $this;
    }

    public function getAuthTokenRefresh(): ?string
    {
        return $this->auth_token_refresh;
    }

    public function setAuthTokenRefresh(string $auth_token_refresh): static
    {
        $this->auth_token_refresh = $auth_token_refresh;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return Collection<int, CustomEvent>
     */
    public function getCustomEvents(): Collection
    {
        return $this->customEvents;
    }

    public function addCustomEvent(CustomEvent $customEvent): static
    {
        if (!$this->customEvents->contains($customEvent)) {
            $this->customEvents->add($customEvent);
            $customEvent->setUser($this);
        }

        return $this;
    }

    public function removeCustomEvent(CustomEvent $customEvent): static
    {
        if ($this->customEvents->removeElement($customEvent)) {
            // set the owning side to null (unless already changed)
            if ($customEvent->getUser() === $this) {
                $customEvent->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomEventParticipation>
     */
    public function getCustomEventParticipations(): Collection
    {
        return $this->customEventParticipations;
    }

    public function addCustomEventParticipation(CustomEventParticipation $customEventParticipation): static
    {
        if (!$this->customEventParticipations->contains($customEventParticipation)) {
            $this->customEventParticipations->add($customEventParticipation);
            $customEventParticipation->setUser($this);
        }

        return $this;
    }

    public function removeCustomEventParticipation(CustomEventParticipation $customEventParticipation): static
    {
        if ($this->customEventParticipations->removeElement($customEventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($customEventParticipation->getUser() === $this) {
                $customEventParticipation->setUser(null);
            }
        }

        return $this;
    }

    public function addFriendRequest(Friend $friendRequest): static
    {
        if (!$this->friendRequests->contains($friendRequest)) {
            $this->friendRequests->add($friendRequest);
            $friendRequest->setUser($this);
        }

        return $this;
    }

    public function removeFriendRequest(Friend $friendRequest): static
    {
        if ($this->friendRequests->removeElement($friendRequest)) {
            // set the owning side to null (unless already changed)
            if ($friendRequest->getUser() === $this) {
                $friendRequest->setUser(null);
            }
        }

        return $this;
    }

    public function addFriend(Friend $friend): static
    {
        if (!$this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->setFriend($this);
        }

        return $this;
    }

    public function removeFriend(Friend $friend): static
    {
        if ($this->friends->removeElement($friend)) {
            // set the owning side to null (unless already changed)
            if ($friend->getFriend() === $this) {
                $friend->setFriend(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Friend>|Friend[] */
    public function getWaitingRequests(): Collection
    {
        return $this->getFriends()->filter(function (Friend $friend) {
            return $friend->getState() === Friend::STATE_REQUESTED;
        });
    }

    /**
     * @return Collection<int, Friend>|Friend[]
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function isFriendWith(Utilisateur $requestedUser)
    {
        $friendRequest = $this->getFriendRequests()
            ->filter(
                function (Friend $friend) use ($requestedUser) {
                    return $friend->getFriend()->getId() == $requestedUser->getId();
                }
            );

        if ($friendRequest->count() >= 1) {
            return $friendRequest->first()->getState();
        }

        $friends = $this->getFriends()
            ->filter(
                function (Friend $friend) use ($requestedUser) {
                    return $friend->getUser()->getId() == $requestedUser->getId();
                }
            );

        if ($friends->count() >= 1) {
            return $friends->first()->getState();
        }

        return Friend::STATE_NOT_REQUESTED;
    }

    /**
     * @return Collection<int, Friend>|Friend[]
     */
    public function getFriendRequests(): Collection
    {
        return $this->friendRequests;
    }

    public function getPublicPlaylists()
    {
        return $this->getPlaylists()->filter(function (Playlist $playlist) {
            return $playlist->isPublic();
        });
    }

    /**
     * @return Collection|Playlist[]
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function scorePlateform(SongDifficulty $songDifficulty, mixed $isVR)
    {
        return $this->getScores()->filter(function (Score $score) use ($songDifficulty, $isVR) {
            return $score->getSongDifficulty() === $songDifficulty && $score->isVR() == $isVR;
        })->first();
    }

    public function addSongsMapped(Song $songsMapped): static
    {
        if (!$this->songsMapped->contains($songsMapped)) {
            $this->songsMapped->add($songsMapped);
            $songsMapped->addMapper($this);
        }

        return $this;
    }

    public function removeSongsMapped(Song $songsMapped): static
    {
        if ($this->songsMapped->removeElement($songsMapped)) {
            $songsMapped->removeMapper($this);
        }

        return $this;
    }

    public function setAvatarDisabled(bool $avatarDisabled): static
    {
        $this->avatarDisabled = $avatarDisabled;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }
}
