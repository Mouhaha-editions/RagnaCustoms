<?php

namespace App\Entity;

use App\Repository\RunSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RunSettingsRepository::class)
 */
class RunSettings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Run::class, inversedBy="runSettings")
     */
    private $run;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity=ChallengeSetting::class, inversedBy="runSettings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $challengeSetting;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRun(): ?Run
    {
        return $this->run;
    }

    public function setRun(?Run $run): self
    {
        $this->run = $run;

        return $this;
    }

    public function getValue(): ?string
    {
        $exp = explode(';', $this->value);
        if (count($exp) > 1) {
            $expexp = explode(':', $exp[0]);
            return $expexp[0];
        }
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getChallengeSetting(): ?ChallengeSetting
    {
        return $this->challengeSetting;
    }

    public function setChallengeSetting(?ChallengeSetting $challengeSetting): self
    {
        $this->challengeSetting = $challengeSetting;

        return $this;
    }

    public function isCompleted()
    {
        $sett = $this->getChallengeSetting();
        $min = $sett->getStepToVictoryMin() == null ? -99999999999 : $sett->getStepToVictoryMin();
        $max = $sett->getStepToVictoryMax() == null ? 99999999999 : $sett->getStepToVictoryMax();

        return $this->getValue() >= $min && $this->getValue() <= $max;
    }
}
