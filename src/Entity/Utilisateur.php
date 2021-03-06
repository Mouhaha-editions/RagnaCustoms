<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\EEmail;
use App\Enum\ENotification;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UtilisateurRepository::class)
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
#[ApiResource(
    collectionOperations: [
     //   "get",
//        "post" => ["security" => "is_granted('ROLE_ADMIN')"],
    ],
    itemOperations: [
        "get",
//        "put" => ["security" => "is_granted('ROLE_ADMIN') or object.owner == user"],
    ],
    normalizationContext: ['groups' => ['read']],
)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $EmailPreference;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $NotificationPreference;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apiKey;
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $certified;
    /**
     * @ORM\ManyToOne(targetEntity=Country::class, inversedBy="utilisateurs")
     */
    #[Groups("read")]
    private $country;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $credits;
    /**
     * @ORM\ManyToMany(targetEntity=SongRequest::class, mappedBy="mapperOnIt")
     */
    private $currentlyMapped;
    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $discordEmail;
    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $discordId;
    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $discordUsername;
    /**
     * @ORM\OneToMany(targetEntity=DownloadCounter::class, mappedBy="user")
     */
    private $downloadCounters;
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email(mode="strict")
     */
    private $email;
    /**
     * @ORM\Column(type="boolean")
     */
    private $enableEmailNotification = false;
    /**
     * @ORM\OneToMany(targetEntity=FollowMapper::class, mappedBy="user")
     */
    private $followedMappers;
    /**
     * @ORM\OneToMany(targetEntity=FollowMapper::class, mappedBy="mapper", orphanRemoval=true)
     */
    private $followers;
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
     * @ORM\OneToMany(targetEntity=Notification::class, mappedBy="user")
     */
    private $notifications;
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
     * @ORM\OrderBy({"updatedAt"="desc"})
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
    #[Groups("read")]
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
        $this->playlists = new ArrayCollection();
        $this->songRequests = new ArrayCollection();
        $this->currentlyMapped = new ArrayCollection();
        $this->songRequestVotes = new ArrayCollection();
        $this->voteCounter = new ArrayCollection();
        $this->followedMappers = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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
        $size = 600;
        return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->email))) . "?d=" . urlencode("https://ragnacustoms.com/apps/runes.png") . "&s=" . $size;
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

    public function hasPlayed(SongDifficulty $difficulty)
    {
        foreach ($this->getScoreHistories() as $scoreHistory) {
            if ($scoreHistory->getSongDifficulty() === $difficulty) {
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
        } else if ($followers < 1000000 / 1000) {
            // Anything less than a million
            $n_format = number_format($followers, 1) . 'K';
        } else if ($followers < 1000000000) {
            // Anything less than a billion
            $n_format = number_format($followers / 1000000, 1) . 'M';
        } else {
            // At least a billion
            $n_format = number_format($followers / 1000000000, 1) . 'B';
        }

        return $n_format . " follower" . ($n_format > 1 ? "s" : "");
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

    public function getEmailPreference(): ?string
    {
        return $this->EmailPreference;
    }

    public function getEmailPreferences()
    {
        return $this->getEmailPreference() == null ? EEmail::cases() : unserialize($this->getEmailPreference());
    }

    public function setEmailPreference(?string $EmailPreference): self
    {
        $this->EmailPreference = $EmailPreference;

        return $this;
    }

    public function getNotificationPreferences()
    {
        return $this->getNotificationPreference() == null ? ENotification::cases() : unserialize($this->getNotificationPreference());
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

    public function hasEmailPreference(ENotification $event)
    {
        return in_array($event, $this->getEmailPreferences());
    }

    public function hasNotificationPreference(ENotification $event)
    {
        return in_array($event, $this->getNotificationPreferences());
    }

}
