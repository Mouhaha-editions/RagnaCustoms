<?php

namespace App\Entity;

use App\Repository\ChallengeRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity(repositoryClass=ChallengeRepository::class)
 */
class Challenge
{
    const TYPE_SOLO = 100;
    const TYPE_SOLO_MULTI = 150;
    const TYPE_MULTI = 200;
    const TYPE_RP = 300;

    const TypesChoices = [
        "Solo" => Challenge::TYPE_SOLO,
        "Solo & multi" => Challenge::TYPE_SOLO_MULTI,
        "Multi" => Challenge::TYPE_MULTI,
        "RolePlay" => Challenge::TYPE_RP,
    ];
    const Types = [
        Challenge::TYPE_SOLO => "Solo",
        Challenge::TYPE_SOLO_MULTI => "Solo & multi",
        Challenge::TYPE_MULTI => "Multi",
        Challenge::TYPE_RP => "RolePlay",
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $banner;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxChallenger;

    /**
     * @ORM\Column(type="datetime")
     */
    private $registrationOpening;

    /**
     * @ORM\Column(type="datetime")
     */
    private $registrationClosing;

    /**
     * @ORM\OneToMany(targetEntity=Participation::class, mappedBy="challenge")
     *
     */
    private $participations;

    /**
     * @ORM\OneToMany(targetEntity=ChallengeDate::class, mappedBy="challenge",cascade={"persist"})
     */
    private $challengeDates;

    /**
     * @ORM\OneToMany(targetEntity=ChallengePrize::class, mappedBy="challenge",cascade={"persist"})
     */
    private $challengePrizes;

    /**
     * @ORM\OneToMany(targetEntity=ChallengeSetting::class, mappedBy="challenge")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $challengeSettings;

    /**
     * @ORM\OneToMany(targetEntity=Run::class, mappedBy="challenge")
     */
    private $runs;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $malusPerRun = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Season::class, inversedBy="Challenges")
     */
    private $season;

    /**
     * @ORM\ManyToMany(targetEntity=Rule::class, mappedBy="challenges",cascade={"persist","remove"})
     * @ORM\OrderBy({"type"="ASC","position"="ASC"})
     */
    private $rules;

    /**
     * @ORM\OneToMany(targetEntity=ChallengeNewsletter::class, mappedBy="challenge", orphanRemoval=true)
     */
    private $challengeNewsletters;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $malusMax;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $display;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="challenges")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $displayTotalInMod;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $displayRulesAndRatiosBeforeStart;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $theFile;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->challengeDates = new ArrayCollection();
        $this->challengePrizes = new ArrayCollection();
        $this->challengeSettings = new ArrayCollection();
        $this->runs = new ArrayCollection();
        $this->challengeNewsletters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isOpen(): bool
    {
        return $this->getRegistrationOpening() <= new DateTime() && new DateTime() <= $this->getRegistrationClosing();
    }

    public function isPast(): bool
    {
        return new DateTime() > $this->getRegistrationClosing();
    }

    public function isFuture(): bool
    {
        return new DateTime() < $this->getRegistrationOpening();
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeStr(): ?string
    {
        return self::Types[$this->type];
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(string $banner): self
    {
        $this->banner = $banner;

        return $this;
    }

    public function getMaxChallenger(): ?int
    {
        return $this->maxChallenger;
    }

    public function setMaxChallenger(int $maxChallenger): self
    {
        $this->maxChallenger = $maxChallenger;

        return $this;
    }

    /**
     * @return DateTimeInterface|DateTime|null
     */
    public function getRegistrationOpening()
    {
        return $this->registrationOpening;
    }

    public function setRegistrationOpening(DateTime $registrationOpening): self
    {
        $this->registrationOpening = $registrationOpening->setTime(0, 0, 0);

        return $this;
    }

    /**
     * @return DateTimeInterface|DateTime|null
     */
    public function getRegistrationClosing()
    {
        return $this->registrationClosing;
    }

    public function setRegistrationClosing(DateTime $registrationClosing): self
    {
        $this->registrationClosing = $registrationClosing->setTime(23, 59, 59);
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
     * @return Collection|Participation[]
     */
    public function getWaitingParticipations(): Collection
    {
        return $this->participations->filter(function ($p) {
            return !$p->getEnabled();
        });
    }

    /**
     * @return Collection|Participation[]
     */
    public function getNoShowParticipations(): Collection
    {
        return $this->participations->filter(function ($p) {
            return $p->getArbitre() == null;
        });
    }

    /**
     * @return Collection|Participation[]
     */
    public function getValidatedParticipations(): Collection
    {
        return $this->participations->filter(function ($p) {
            return $p->getEnabled();
        });
    }

    public function addParticipation(Participation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setChallenge($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): self
    {
        if ($this->participations->contains($participation)) {
            $this->participations->removeElement($participation);
            // set the owning side to null (unless already changed)
            if ($participation->getChallenge() === $this) {
                $participation->setChallenge(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ChallengeDate[]
     */
    public function getChallengeDates(): Collection
    {
        return $this->challengeDates;
    }

    public function addChallengeDate(ChallengeDate $challengeDate): self
    {
        if (!$this->challengeDates->contains($challengeDate)) {
            $this->challengeDates[] = $challengeDate;
            $challengeDate->setChallenge($this);
        }

        return $this;
    }

    public function removeChallengeDate(ChallengeDate $challengeDate): self
    {
        if ($this->challengeDates->contains($challengeDate)) {
            $this->challengeDates->removeElement($challengeDate);
            // set the owning side to null (unless already changed)
            if ($challengeDate->getChallenge() === $this) {
                $challengeDate->setChallenge(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ChallengePrize[]
     */
    public function getChallengePrizes(): Collection
    {
        return $this->challengePrizes;
    }

    public function getCashPrize()
    {
        $sum = 0;
        foreach ($this->getChallengePrizes() as $prize) {
            $sum += $prize->getValue();
        }
        return $sum;
    }

    public function addChallengePrize(ChallengePrize $challengePrize): self
    {
        if (!$this->challengePrizes->contains($challengePrize)) {
            $this->challengePrizes[] = $challengePrize;
            $challengePrize->setChallenge($this);
        }

        return $this;
    }

    public function removeChallengePrize(ChallengePrize $challengePrize): self
    {
        if ($this->challengePrizes->contains($challengePrize)) {
            $this->challengePrizes->removeElement($challengePrize);
            // set the owning side to null (unless already changed)
            if ($challengePrize->getChallenge() === $this) {
                $challengePrize->setChallenge(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ChallengeSetting[]
     */
    public function getChallengeSettings(): Collection
    {
        return $this->challengeSettings;
    }

    public function addChallengeSetting(ChallengeSetting $challengeSetting): self
    {
        if (!$this->challengeSettings->contains($challengeSetting)) {
            $this->challengeSettings[] = $challengeSetting;
            $challengeSetting->setChallenge($this);
        }

        return $this;
    }

    public function removeChallengeSetting(ChallengeSetting $challengeSetting): self
    {
        if ($this->challengeSettings->contains($challengeSetting)) {
            $this->challengeSettings->removeElement($challengeSetting);
            // set the owning side to null (unless already changed)
            if ($challengeSetting->getChallenge() === $this) {
                $challengeSetting->setChallenge(null);
            }
        }

        return $this;
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
            $run->setChallenge($this);
        }

        return $this;
    }

    public function removeRun(Run $run): self
    {
        if ($this->runs->contains($run)) {
            $this->runs->removeElement($run);
            // set the owning side to null (unless already changed)
            if ($run->getChallenge() === $this) {
                $run->setChallenge(null);
            }
        }

        return $this;
    }

    public function getMalusPerRun(): ?string
    {
        return $this->malusPerRun;
    }

    public function setMalusPerRun(string $malusPerRun): self
    {
        $this->malusPerRun = $malusPerRun;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    /**
     * @return Collection|Rule[]
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(Rule $rule): self
    {
        if (!$this->rules->contains($rule)) {
            $this->rules[] = $rule;
            $rule->addChallenge($this);
        }

        return $this;
    }

    public function removeRule(Rule $rule): self
    {
        if ($this->rules->contains($rule)) {
            $this->rules->removeElement($rule);
            $rule->removeChallenge($this);
        }

        return $this;
    }

    /**
     * @return Collection|ChallengeNewsletter[]
     */
    public function getChallengeNewsletters(): Collection
    {
        return $this->challengeNewsletters;
    }

    public function addChallengeNewsletter(ChallengeNewsletter $challengeNewsletter): self
    {
        if (!$this->challengeNewsletters->contains($challengeNewsletter)) {
            $this->challengeNewsletters[] = $challengeNewsletter;
            $challengeNewsletter->setChallenge($this);
        }

        return $this;
    }

    public function removeChallengeNewsletter(ChallengeNewsletter $challengeNewsletter): self
    {
        if ($this->challengeNewsletters->contains($challengeNewsletter)) {
            $this->challengeNewsletters->removeElement($challengeNewsletter);
            // set the owning side to null (unless already changed)
            if ($challengeNewsletter->getChallenge() === $this) {
                $challengeNewsletter->setChallenge(null);
            }
        }

        return $this;
    }

    public function getLeaderBoard()
    {
        $participations = $this->getValidatedParticipations()->toArray();
        $challenge = $this;
        usort($participations, function (Participation $a, Participation $b) use ($challenge) {
            if ($a->getUser()->getBestScore($challenge) == $b->getUser()->getBestScore($challenge)) {
                return 0;
            }
            return ($a->getUser()->getBestScore($challenge) > $b->getUser()->getBestScore($challenge)) ? -1 : 1;
        });

        return $participations;
    }

    public function getWinner()
    {
        $leaderboard = $this->getLeaderBoard();

        if($leaderboard == null){
            return null;
        }
        /** @var User $user */
        $user= $leaderboard[0]->getUser();
        $score = $user->getBestScore($this);
        if($score <= 0){
            return null;
        }
        return $leaderboard != null ? $leaderboard[0] : null;
    }

    public function getMalusMax(): ?string
    {
        return $this->malusMax;
    }

    public function setMalusMax(string $malusMax): self
    {
        $this->malusMax = $malusMax;

        return $this;
    }

    public function getDisplay(): ?bool
    {
        return $this->display;
    }

    public function setDisplay(?bool $display): self
    {
        $this->display = $display;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDisplayTotalInMod(): ?bool
    {
        return $this->displayTotalInMod;
    }

    public function setDisplayTotalInMod(?bool $displayTotalInMod): self
    {
        $this->displayTotalInMod = $displayTotalInMod;

        return $this;
    }

    public function getDisplayRulesAndRatiosBeforeStart(): ?bool
    {
        return $this->displayRulesAndRatiosBeforeStart;
    }

    public function setDisplayRulesAndRatiosBeforeStart(bool $displayRulesAndRatiosBeforeStart): self
    {
        $this->displayRulesAndRatiosBeforeStart = $displayRulesAndRatiosBeforeStart;

        return $this;
    }

    public function isStarted()
    {
        foreach($this->getChallengeDates() AS $dates){
            if($dates->getStartDate() <= new DateTime()){
                return true;
            }
        }
        return false;
    }

    public function getTheFile(): ?string
    {
        return $this->theFile;
    }

    public function setTheFile(string $theFile): self
    {
        $this->theFile = $theFile;

        return $this;
    }
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
