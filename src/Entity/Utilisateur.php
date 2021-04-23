<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UtilisateurRepository::class)
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class Utilisateur implements UserInterface
{
    use TimestampableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\OneToMany(targetEntity=Song::class, mappedBy="user")
     */
    private $songs;

    /**
     * @ORM\OneToMany(targetEntity=Vote::class, mappedBy="user", orphanRemoval=true)
     */
    private $votes;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $certified;

    /**
     * @ORM\OneToMany(targetEntity=DownloadCounter::class, mappedBy="user")
     */
    private $downloadCounters;
    /**
     * @ORM\OneToMany(targetEntity=ViewCounter::class, mappedBy="user")
     */
    private $viewCounters;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $steamCommunityId;

    /**
     * @ORM\OneToMany(targetEntity=Score::class, mappedBy="user")
     */
    private $scores;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isMapper;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_img;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $mailingNewSong = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mapper_discord;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apiKey;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->downloadCounters = new ArrayCollection();
        $this->viewCounters = new ArrayCollection();
        $this->scores = new ArrayCollection();
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
        return (string) $this->username;
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
        return (string) $this->password;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection|Song[]
     */
    public function getSongs(): Collection
    {
        return $this->songs;
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

    /**
     * @return Collection|Vote[]
     */
    public function getVotes(): Collection
    {
        return $this->votes;
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

    public function hasVotedUpFor(Song $song) :bool
    {
        foreach($this->getVotes() As $vote){
            if($song !== $vote->getSong()){continue;}
            if($vote->getKind() == Vote::KIND_UP){
                return true;
            }
        }
        return false;
    }

    public function hasVotedDownFor(Song $song) :bool
    {
        foreach($this->getVotes() As $vote){
            if($song !== $vote->getSong()){continue;}
            if($vote->getKind() == Vote::KIND_DOWN){
                return true;
            }
        }
        return false;
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

    /**
     * @return Collection|ViewCounter[]
     */
    public function getViewCounters(): Collection
    {
        return $this->viewCounters;
    }

    public function addViewCounter(ViewCounter $viewCounters): self
    {
        if (!$this->viewCounters->contains($viewCounters)) {
            $this->viewCounters[] = $viewCounters;
            $viewCounters->setUser($this);
        }

        return $this;
    }

    public function removeViewCounter(ViewCounter $viewCounters): self
    {
        if ($this->viewCounters->removeElement($viewCounters)) {
            // set the owning side to null (unless already changed)
            if ($viewCounters->getUser() === $this) {
                $viewCounters->setUser(null);
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

    public function setIsMapper(?bool $isMapper): self
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

    public function setMapperDiscord(string $mapper_discord): self
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
}
