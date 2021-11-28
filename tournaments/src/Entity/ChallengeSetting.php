<?php

namespace App\Entity;

use App\Repository\ChallengeSettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChallengeSettingRepository::class)
 */
class ChallengeSetting
{
    const TEXT = 100;
    const SELECT = 200;
    const CHECKBOX = 300;
    const NUMERIC = 400;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=5, nullable=true)
     */
    private $ratio = 1;

    /**
     * @ORM\ManyToOne(targetEntity=Challenge::class, inversedBy="challengeSettings")
     */
    private $challenge;

    /**
     * @ORM\Column(type="integer")
     */
    private $inputType = self::TEXT;

    /**
     * @ORM\Column(type="string",length=255, nullable=true)
     */
    private $defaultValue ;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isUsedForScore = true;

    /**
     * @ORM\OneToMany(targetEntity=RunSettings::class, mappedBy="challengeSetting")
     */
    private $runSettings;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isStepToVictory = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $stepToVictoryMin;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $stepToVictoryMax;

    /**
     * @ORM\Column(type="boolean")
     */
    private $displayForStats = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $displayBestForStats = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $statLabel;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isReportedOnTheNextRun;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isAffectedByMalus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subTotal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $autoValue;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sendToMod;

    public function __construct()
    {
        $this->runSettings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getRatio(): ?string
    {
        return $this->ratio;
    }

    public function setRatio(?string $ratio): self
    {
        $this->ratio = $ratio;

        return $this;
    }

    public function getChallenge(): ?Challenge
    {
        return $this->challenge;
    }

    public function setChallenge(?Challenge $challenge): self
    {
        $this->challenge = $challenge;

        return $this;
    }

    public function getInputType(): ?int
    {
        return $this->inputType;
    }

    public function setInputType(int $inputType): self
    {
        $this->inputType = $inputType;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getIsUsedForScore(): ?bool
    {
        return $this->isUsedForScore;
    }

    public function setIsUsedForScore(bool $isUsedForScore): self
    {
        $this->isUsedForScore = $isUsedForScore;

        return $this;
    }

    /**
     * @return Collection|RunSettings[]
     */
    public function getRunSettings(): Collection
    {
        return $this->runSettings;
    }

    public function addRunSetting(RunSettings $runSetting): self
    {
        if (!$this->runSettings->contains($runSetting)) {
            $this->runSettings[] = $runSetting;
            $runSetting->setChallengeSetting($this);
        }

        return $this;
    }

    public function removeRunSetting(RunSettings $runSetting): self
    {
        if ($this->runSettings->contains($runSetting)) {
            $this->runSettings->removeElement($runSetting);
            // set the owning side to null (unless already changed)
            if ($runSetting->getChallengeSetting() === $this) {
                $runSetting->setChallengeSetting(null);
            }
        }

        return $this;
    }

    public function getIsStepToVictory(): ?bool
    {
        return $this->isStepToVictory;
    }

    public function setIsStepToVictory(bool $isStepToVictory): self
    {
        $this->isStepToVictory = $isStepToVictory;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getStepToVictoryMin(): ?string
    {
        return $this->stepToVictoryMin;
    }

    public function setStepToVictoryMin(?string $stepToVictoryMin): self
    {
        $this->stepToVictoryMin = $stepToVictoryMin;

        return $this;
    }

    public function getStepToVictoryMax(): ?string
    {
        return $this->stepToVictoryMax;
    }

    public function setStepToVictoryMax(?string $stepToVictoryMax): self
    {
        $this->stepToVictoryMax = $stepToVictoryMax;

        return $this;
    }

    public function getDisplayForStats(): ?bool
    {
        return $this->displayForStats;
    }

    public function setDisplayForStats(bool $displayForStats): self
    {
        $this->displayForStats = $displayForStats;

        return $this;
    }

    public function getDisplayBestForStats(): ?bool
    {
        return $this->displayBestForStats;
    }

    public function setDisplayBestForStats(bool $displayBestForStats): self
    {
        $this->displayBestForStats = $displayBestForStats;

        return $this;
    }

    public function getStatLabel(): ?string
    {
        return $this->statLabel;
    }

    public function setStatLabel(?string $statLabel): self
    {
        $this->statLabel = $statLabel;

        return $this;
    }

    public function getIsReportedOnTheNextRun(): ?bool
    {
        return $this->isReportedOnTheNextRun;
    }

    public function setIsReportedOnTheNextRun(?bool $isReportedOnTheNextRun): self
    {
        $this->isReportedOnTheNextRun = $isReportedOnTheNextRun;

        return $this;
    }

    public function getIsAffectedByMalus(): ?bool
    {
        return $this->isAffectedByMalus;
    }

    public function setIsAffectedByMalus(?bool $isAffectedByMalus): self
    {
        $this->isAffectedByMalus = $isAffectedByMalus;

        return $this;
    }

    public function getSubTotal(): ?string
    {
        return $this->subTotal;
    }

    public function setSubTotal(?string $subTotal): self
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    public function getAutoValue(): ?string
    {
        return $this->autoValue;
    }

    public function setAutoValue(?string $autoValue): self
    {
        $this->autoValue = $autoValue;

        return $this;
    }

    public function getSendToMod(): ?bool
    {
        return $this->sendToMod;
    }

    public function setSendToMod(?bool $sendToMod): self
    {
        $this->sendToMod = $sendToMod;

        return $this;
    }

}
