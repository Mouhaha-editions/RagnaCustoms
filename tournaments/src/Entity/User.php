<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface
{
    use TimestampableEntity;

    const NIVEAU_DEBUTANT = 10;
    const NIVEAU_INTERMEDIAIRE = 20;
    const NIVEAU_CONFIRMED = 30;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

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
     * @ORM\OneToMany(targetEntity=UserScore::class, mappedBy="user")
     */
    private $userScores;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $discordID;

    /**
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="user")
     */
    private $participations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $twitchID;

    /**
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="arbitre")
     */
    private $arbitreOf;

    /**
     * @ORM\OneToMany(targetEntity=Run::class, mappedBy="user")
     */
    private $runs;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $babyProof;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $apiKey;

    /**
     * @ORM\OneToMany(targetEntity=Challenge::class, mappedBy="user")
     */
    private $challenges;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bannerlordID;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $steamID;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $twitter = "https://twitter.com/...";

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $instagram = "https://www.instagram.com/...";

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $youtube = "https://www.youtube.com/channel/...";

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $levelMulti;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $displayOnTv;


    public function __construct()
    {
        $this->userScores = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->arbitreOf = new ArrayCollection();
        $this->runs = new ArrayCollection();
        $this->challenges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function md5Email()
    {
        return md5(strtolower(trim($this->getEmail())));
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @param $username
     * @return string
     *
     */
    public function setUsername($username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername(): string
    {
        return (string)$this->username;
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
        return (string)$this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|UserScore[]
     */
    public function getUserScores(): Collection
    {
        return $this->userScores;
    }

    public function addUserScore(UserScore $userScore): self
    {
        if (!$this->userScores->contains($userScore)) {
            $this->userScores[] = $userScore;
            $userScore->setUser($this);
        }

        return $this;
    }

    public function removeUserScore(UserScore $userScore): self
    {
        if ($this->userScores->contains($userScore)) {
            $this->userScores->removeElement($userScore);
            // set the owning side to null (unless already changed)
            if ($userScore->getUser() === $this) {
                $userScore->setUser(null);
            }
        }

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getDiscordID(): ?string
    {
        return $this->discordID;
    }

    public function setDiscordID(?string $discordID): self
    {
        $this->discordID = $discordID;

        return $this;
    }

    /**
     * @return Collection|Participation[]
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    /**
     * @param Challenge $challenge
     * @return Participation|null
     */
    public function getParticipation(Challenge $challenge)
    {
        foreach ($this->getParticipations() as $participation) {
            if ($participation->getChallenge() === $challenge) {
                return $participation;
            }
        }
        return null;
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setUser($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->contains($participation)) {
            $this->participations->removeElement($participation);
            // set the owning side to null (unless already changed)
            if ($participation->getUser() === $this) {
                $participation->setUser(null);
            }
        }

        return $this;
    }

    public function getFullNameUsername()
    {
        return $this->getFirstname() . " " . $this->getLastname() . " (" . $this->getUsername() . ")";
    }

    public function getFullName()
    {
        return $this->getFirstname() . " " . $this->getLastname();
    }

    public function getTwitchID(): ?string
    {
        return $this->twitchID;
    }

    public function setTwitchID(?string $twitchID): self
    {
        $this->twitchID = $twitchID;

        return $this;
    }

    /**
     * @return Collection|Participation[]
     */
    public function getArbitreOf(): Collection
    {
        return $this->arbitreOf;
    }

    public function addArbitreOf(Participation $arbitreOf): self
    {
        if (!$this->arbitreOf->contains($arbitreOf)) {
            $this->arbitreOf[] = $arbitreOf;
            $arbitreOf->setArbitre($this);
        }

        return $this;
    }

    public function removeArbitreOf(Participation $arbitreOf): self
    {
        if ($this->arbitreOf->contains($arbitreOf)) {
            $this->arbitreOf->removeElement($arbitreOf);
            // set the owning side to null (unless already changed)
            if ($arbitreOf->getArbitre() === $this) {
                $arbitreOf->setArbitre(null);
            }
        }

        return $this;
    }

    public function getTwitchArbitreOf()
    {
        return $this->getArbitreOf()->filter(function (Participation $p) {
            return !empty($p->getUser()->getTwitchID());
        });
    }

    public function getDiscordArbitreOf()
    {
        return $this->getArbitreOf()->filter(function (Participation $p) {
            return !empty($p->getUser()->getDiscordID()) && empty($p->getUser()->getTwitchID());
        });
    }

    /**
     * @return Collection|Run[]
     */
    public function getRuns(): Collection
    {
        return $this->runs;
    }

    public function addRun(Run $run): self
    {
        if (!$this->runs->contains($run)) {
            $this->runs[] = $run;
            $run->setUser($this);
        }

        return $this;
    }

    public function removeRun(Run $run): self
    {
        if ($this->runs->contains($run)) {
            $this->runs->removeElement($run);
            // set the owning side to null (unless already changed)
            if ($run->getUser() === $this) {
                $run->setUser(null);
            }
        }

        return $this;
    }

    public function countRun(Challenge $challenge)
    {
        return $this->getRuns()->filter(function (Run $run) use ($challenge) {
            return $run->getChallenge() === $challenge && $run->getTraining() == false;
        })->Count();
    }

    public function getCurrentScore(Challenge $challenge)
    {
        /** @var Run $run */
        $run = $this->getRuns()->filter(function (Run $r) use ($challenge) {
            return $r->getChallenge() === $challenge && $r->getTraining() == false;
        })->last();

        return !$run ? 0 : $run->getComputedScore();
    }

    public function getNonMalusableScore(Challenge $challenge)
    {
        /** @var Run $run */
        $run = $this->getRuns()->filter(function ($r) use ($challenge) {
            return $r->getChallenge() === $challenge && $r->getTraining() == false;
        })->last();
        if ($run === false) {
            return 0;
        }
        $score = 0;
        foreach ($run->getRunSettings() as $setting) {
            if (!$setting->getChallengeSetting()->getIsAffectedByMalus()) {
                if (is_numeric($setting->getValue())) {
                    $score += $setting->getValue();
                }
            }
        }
        return $score;
    }

    public function getBestScore(Challenge $challenge)
    {
        $runs = $this->getRuns()->filter(function (Run $run) use ($challenge) {
            return $run->getChallenge() === $challenge && $run->getTraining() == false;
        });
        $score = -99999999;
        if (count($runs) > 0) {
            /** @var Run $run */
            foreach ($runs as $run) {
                $comp = $run->getComputedScore();
                if ($score <= $comp) {
                    $score = $comp;
                }
            }
        }
        return $score;
    }

    public function runCumulSettingValue(ChallengeSetting $setting, Challenge $challenge)
    {

        $cumul = 0;
        /** @var Run[] $runs */
        $runs = $this->getRuns()->filter(function (Run $run) use ($challenge) {
            return $run->getChallenge() === $challenge && $run->getTraining() == false;
        });

        foreach ($runs as $run) {
            foreach ($run->getRunSettings() as $runSetting) {
                if ($runSetting->getChallengeSetting() === $setting) {
                    $cumul += $runSetting->getValue();
                }
            }
        }

        return $cumul;
    }

    public function runBestSettingValue(ChallengeSetting $setting, Challenge $challenge)
    {
        $best = 0;
        /** @var Run[] $runs */
        $runs = $this->getRuns()->filter(function (Run $run) use ($challenge) {
            return $run->getChallenge() === $challenge && $run->getTraining() == false;
        });

        foreach ($runs as $run) {
            foreach ($run->getRunSettings() as $runSetting) {
                if ($runSetting->getChallengeSetting() === $setting && $runSetting->getValue() > $best) {
                    $best = $runSetting->getValue();
                }
            }
        }
        return $best;
    }

    public function getBabyProof(): ?bool
    {
        return $this->babyProof;
    }

    public function setBabyProof(?bool $babyProof): self
    {
        $this->babyProof = $babyProof;

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

    /**
     * @return Collection|Challenge[]
     */
    public function getChallenges(): Collection
    {
        return $this->challenges;
    }

    public function addChallenge(Challenge $challenge): self
    {
        if (!$this->challenges->contains($challenge)) {
            $this->challenges[] = $challenge;
            $challenge->setUser($this);
        }

        return $this;
    }

    public function removeChallenge(Challenge $challenge): self
    {
        if ($this->challenges->contains($challenge)) {
            $this->challenges->removeElement($challenge);
            // set the owning side to null (unless already changed)
            if ($challenge->getUser() === $this) {
                $challenge->setUser(null);
            }
        }

        return $this;
    }

    public function getBannerlordID(): ?string
    {
        return $this->bannerlordID;
    }

    public function setBannerlordID(?string $bannerlordID): self
    {
        $this->bannerlordID = $bannerlordID;

        return $this;
    }

    public function getSteamID(): ?string
    {
        return $this->steamID;
    }

    public function setSteamID(?string $steamID): self
    {
        $this->steamID = $steamID;

        return $this;
    }

    /**
     * @return string
     */
    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    /**
     * @param string $twitter
     * @return User
     */
    public function setTwitter(?string $twitter): User
    {
        $this->twitter = $twitter;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    /**
     * @param string $instagram
     * @return User
     */
    public function setInstagram(?string $instagram): User
    {
        $this->instagram = $instagram;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLevelMulti()
    {
        return $this->levelMulti;
    }

    /**
     * @param mixed $levelMulti
     * @return User
     */
    public function setLevelMulti($levelMulti)
    {
        $this->levelMulti = $levelMulti;
        return $this;
    }

    /**
     * @return string
     */
    public function getYoutube()
    {
        return $this->youtube;
    }

    /**
     * @param string $youtube
     * @return User
     */
    public function setYoutube(?string $youtube)
    {
        $this->youtube = $youtube;
        return $this;
    }

    public function getTrainingRun(Challenge $challenge)
    {
        $tmp = [];
        /** @var Run $run */
        foreach($this->getRuns() AS $run){
            if($run->getTraining() && $run->getChallenge() === $challenge){
                $tmp[] = $run;
            }
        }
        return array_reverse($tmp);
    }

    public function getDisplayOnTv(): ?bool
    {
        return $this->displayOnTv;
    }

    public function setDisplayOnTv(?bool $displayOnTv): self
    {
        $this->displayOnTv = $displayOnTv;

        return $this;
    }
}
