<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\WanadevApiController;
use App\Repository\ScoreRepository;
use App\Service\StatisticService;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ApiResource(
    collectionOperations: [
        "get",
        //    "post" => ["security" => "is_granted('ROLE_ADMIN')"],
    ],
    itemOperations: [
        "get",
        //        "put" => ["security" => "is_granted('ROLE_ADMIN') or object.owner == user"],
    ])]
#[ORM\Table(name: 'score')]
#[ORM\UniqueConstraint(name: 'user_difficulty_2', columns: [
    'user_id',
    'song_difficulty_id',
    'plateform'
])]
#[ORM\Entity(repositoryClass: ScoreRepository::class)]
class Score
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $comboBlue;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $comboYellow;
    #[ORM\Column(type: 'text', nullable: true)]
    private $country;
    #[ORM\Column(type: 'text', nullable: true)]
    private $dateRagnarock;
    #[ORM\Column(type: 'text', nullable: true)]
    private $extra;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $hit;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $hitDeltaAverage;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $hitPercentage;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $missed;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $percentageOfPerfects;
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $plateform;
    #[ORM\Column(type: 'float', nullable: true)]
    private $rawPP;
    #[ORM\Column(type: 'float')]
    private $score;
    #[ORM\Column(type: 'text', nullable: true)]
    private $session;
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\ManyToOne(targetEntity: SongDifficulty::class, inversedBy: 'scores')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private $songDifficulty;
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'scores')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\Column(type: 'text', nullable: true)]
    private $userRagnarock;
    #[ORM\Column(type: 'float', nullable: true)]
    private $weightedPP;

    #[ORM\Column]
    private ?\DateTimeImmutable $playedAt = null;

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

    public function getScoreDisplay(): ?string
    {
        return $this->score / 100;
    }

    public function getHumanUpdatedAt(): ?string
    {
        return StatisticService::dateDisplay($this->createdAt);
    }

    public function getTimeAgo(): string
    {
        return StatisticService::dateDisplay($this->getPlayedAt());
    }

    /**
     * @return float|null
     */
    public function getRawPP(): ?float
    {
        return $this->rawPP;
    }

    /**
     * @param  float|null  $rawPP
     * @return $this
     */
    public function setRawPP(?float $rawPP): self
    {
        $this->rawPP = $rawPP;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getWeightedPP(): ?float
    {
        return $this->weightedPP;
    }

    /**
     * @param  float|null  $weightedPP
     * @return $this
     */
    public function setWeightedPP(?float $weightedPP): self
    {
        $this->weightedPP = $weightedPP;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPercentageOfPerfects()
    {
        return $this->percentageOfPerfects;
    }

    /**
     * @param  mixed  $percentageOfPerfects
     */
    public function setPercentageOfPerfects($percentageOfPerfects): void
    {
        $this->percentageOfPerfects = $percentageOfPerfects;
    }

    /**
     * @return mixed
     */
    public function getComboBlue()
    {
        return $this->comboBlue;
    }

    /**
     * @param  mixed  $comboBlue
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
     * @param  mixed  $comboYellow
     */
    public function setComboYellow($comboYellow): void
    {
        $this->comboYellow = $comboYellow;
    }

    /**
     * @return mixed
     */
    public function getMissed()
    {
        return $this->missed;
    }

    /**
     * @param  mixed  $missed
     */
    public function setMissed($missed): void
    {
        $this->missed = $missed;
    }

    /**
     * @return mixed
     */
    public function getHit()
    {
        return $this->hit;
    }

    /**
     * @param  mixed  $hit
     */
    public function setHit($hit): void
    {
        $this->hit = $hit;
    }

    /**
     * @return mixed
     */
    public function getHitPercentage()
    {
        return $this->hitPercentage;
    }

    /**
     * @param  mixed  $hitPercentage
     */
    public function setHitPercentage($hitPercentage): void
    {
        $this->hitPercentage = $hitPercentage;
    }

    /**
     * @return mixed
     */
    public function getHitDeltaAverage()
    {
        return $this->hitDeltaAverage;
    }

    /**
     * @param  mixed  $hitDeltaAverage
     */
    public function setHitDeltaAverage($hitDeltaAverage): void
    {
        $this->hitDeltaAverage = $hitDeltaAverage;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param  mixed  $extra
     */
    public function setExtra($extra): void
    {
        $this->extra = $extra;
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param  mixed  $session
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
     * @param  mixed  $dateRagnarock
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
     * @param  mixed  $userRagnarock
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
     * @param  mixed  $country
     */
    public function setCountry($country): void
    {
        $this->country = $country;
    }

    public function getTimeAgoShort()
    {
        return StatisticService::dateDisplayedShort($this->getPlayedAt());
    }

    public function getWeightPercentage(): float
    {
        return $this->getRawPP() > 0 ? (100 * $this->getWeightedPP() / $this->getRawPP()) : 0;
    }

    public function getPlateformIcon()
    {
        return in_array($this->getPlateform(),WanadevApiController::VR_PLATEFORM) ? 'fa-vr-cardboard' : 'fa-gamepad';
    }

    public function isVR(): bool
    {
        return in_array($this->getPlateform(),WanadevApiController::VR_PLATEFORM);
    }

    /**
     * @return mixed
     */
    public function getPlateform()
    {
        return $this->plateform;
    }

    /**
     * @param  mixed  $plateform
     */
    public function setPlateform($plateform): void
    {
        $this->plateform = $plateform;
    }

    public function isRankable()
    {
        return $this->getSongDifficulty()->isRanked();
    }

    /**
     * @return SongDifficulty|null
     */
    public function getSongDifficulty(): ?SongDifficulty
    {
        return $this->songDifficulty;
    }

    /**
     * @param  SongDifficulty|null  $SongDifficulty
     * @return $this
     */
    public function setSongDifficulty(?SongDifficulty $SongDifficulty): self
    {
        $this->songDifficulty = $SongDifficulty;

        return $this;
    }

    public function isOKODO(): bool
    {
        return in_array($this->plateform, WanadevApiController::OKOD_PLATEFORM);
    }

    public function getPlayedAt(): ?\DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(\DateTimeImmutable $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }
}
