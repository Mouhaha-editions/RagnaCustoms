<?php

namespace App\Entity;

use App\Repository\ScoreHistoryRepository;
use App\Service\StatisticService;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScoreHistoryRepository::class)
 */
class ScoreHistory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    use TimestampableEntity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $comboBlue;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $comboYellow;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $country;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $dateRagnarock;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hit;
    /**
     * @ORM\Column(type="decimal", precision=20, scale=6, nullable=true)
     */
    private $hitAccuracy;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hitDeltaAverage;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $hitPercentage;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $missed;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $percentageOfPerfects;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $plateform;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $rawPP;
    /**
     * @ORM\Column(type="float")
     */
    private $score;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $session;
    /**
     * @ORM\ManyToOne(targetEntity=SongDifficulty::class, inversedBy="scoreHistories")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $songDifficulty;
    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="scoreHistories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $userRagnarock;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getSongDifficulty(): ?SongDifficulty
    {
        return $this->songDifficulty;
    }

    public function setSongDifficulty(?SongDifficulty $songDifficulty): self
    {
        $this->songDifficulty = $songDifficulty;

        return $this;
    }

    public function getRawPP(): ?float
    {
        return $this->rawPP;
    }

    public function setRawPP(?float $rawPP): self
    {
        $this->rawPP = $rawPP;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getComboBlue()
    {
        return $this->comboBlue;
    }

    /**
     * @param mixed $comboBlue
     */
    public function setComboBlue($comboBlue): void
    {
        $this->comboBlue = $comboBlue;
    }

    /**
     * @return mixed
     */
    public function getComboYellow()
    {
        return $this->comboYellow;
    }

    /**
     * @param mixed $comboYellow
     */
    public function setComboYellow($comboYellow): void
    {
        $this->comboYellow = $comboYellow;
    }

    /**
     * @return mixed
     */
    public function getHit()
    {
        return $this->hit;
    }

    /**
     * @param mixed $hit
     */
    public function setHit($hit): void
    {
        $this->hit = $hit;
    }

    /**
     * @return mixed
     */
    public function getHitDeltaAverage()
    {
        return $this->hitDeltaAverage;
    }

    /**
     * @param mixed $hitDeltaAverage
     */
    public function setHitDeltaAverage($hitDeltaAverage): void
    {
        $this->hitDeltaAverage = $hitDeltaAverage;
    }

    /**
     * @return mixed
     */
    public function getHitPercentage()
    {
        return $this->hitPercentage;
    }

    /**
     * @param mixed $hitPercentage
     */
    public function setHitPercentage($hitPercentage): void
    {
        $this->hitPercentage = $hitPercentage;
    }

    /**
     * @return mixed
     */
    public function getMissed()
    {
        return $this->missed;
    }

    /**
     * @param mixed $missed
     */
    public function setMissed($missed): void
    {
        $this->missed = $missed;
    }

    /**
     * @return mixed
     */
    public function getPercentageOfPerfects()
    {
        return $this->percentageOfPerfects;
    }

    /**
     * @param mixed $percentageOfPerfects
     */
    public function setPercentageOfPerfects($percentageOfPerfects): void
    {
        $this->percentageOfPerfects = $percentageOfPerfects;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $extra
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }

    public function getScoreDisplay(): ?string
    {
        return $this->score / 100;
    }

    public function getHumanUpdatedAt(): ?string
    {
        return StatisticService::dateDiplayer($this->createdAt);
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     */
    public function setSession($session): void
    {
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    public function getDateRagnarock()
    {
        return $this->dateRagnarock;
    }

    /**
     * @param mixed $dateRagnarock
     */
    public function setDateRagnarock($dateRagnarock): void
    {
        $this->dateRagnarock = $dateRagnarock;
    }

    /**
     * @return mixed
     */
    public function getUserRagnarock()
    {
        return $this->userRagnarock;
    }

    /**
     * @param mixed $userRagnarock
     */
    public function setUserRagnarock($userRagnarock): void
    {
        $this->userRagnarock = $userRagnarock;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country): void
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getPlateform()
    {
        return $this->plateform;
    }

    /**
     * @param mixed $plateform
     */
    public function setPlateform($plateform): void
    {
        $this->plateform = $plateform;
    }

    public function getTimeAgo()
    {
        return StatisticService::dateDiplayer($this->getCreatedAt());
    }

    public function getTimeAgoShort()
    {
       return StatisticService::dateDiplayerShort($this->getCreatedAt());
    }
}
