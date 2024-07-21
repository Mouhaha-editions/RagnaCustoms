<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\WanadevApiController;
use App\Repository\ScoreHistoryRepository;
use App\Service\StatisticService;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;


#[ApiResource(
    operations: [new GetCollection()],
    normalizationContext: ['groups' => ['song:get']],
    denormalizationContext: ['groups' => ['song:get']],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Entity(repositoryClass: ScoreHistoryRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['songDifficulty' => 'exact'] )]
#[ApiFilter(DateFilter::class, properties: ['updatedAt', 'createdAt'] )]

class ScoreHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['song:get'])]
    private ?int $id;

    use TimestampableEntity;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $comboBlue;
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $comboYellow;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $country;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $dateRagnarock;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $extra;
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $hit;
    #[ORM\Column(type: 'decimal', precision: 20, scale: 6, nullable: true)]
    #[Groups(['song:get'])]
    private ?string $hitAccuracy;
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $hitDeltaAverage;
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $hitPercentage;
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $missed;
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['song:get'])]
    private ?int $percentageOfPerfects;
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['song:get'])]
    private ?string $plateform;
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $rawPP;
    #[ORM\Column(type: 'float')]
    #[Groups(['song:get'])]
    private float $score;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $session;
    #[ORM\ManyToOne(targetEntity: SongDifficulty::class, inversedBy: 'scoreHistories')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private SongDifficulty $songDifficulty;
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'scoreHistories')]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $user;
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['song:get'])]
    private ?string $userRagnarock;

    #[ORM\Column]
    private ?\DateTimeImmutable $played_at = null;

    #[Groups(['song:get'])]
    public function getUsername(): string
    {
        return $this->getUser()->getUsername();
    }

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
        return StatisticService::dateDisplay($this->createdAt);
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
        return StatisticService::dateDisplay($this->getCreatedAt());
    }

    public function getTimeAgoShort()
    {
       return StatisticService::dateDisplayedShort($this->getCreatedAt());
    }

    public function getHitAccuracy(): ?string
    {
        return $this->hitAccuracy;
    }

    public function setHitAccuracy(?string $hitAccuracy): self
    {
        $this->hitAccuracy = $hitAccuracy;

        return $this;
    }

    public function getPlateformIcon()
    {
        return in_array($this->getPlateform(),WanadevApiController::VR_PLATEFORM) ? 'fa-vr-cardboard' : 'fa-gamepad';
    }

    public function isVR(): bool
    {
        return in_array($this->getPlateform(),WanadevApiController::VR_PLATEFORM);
    }

    public function getPlayedAt(): ?\DateTimeImmutable
    {
        return $this->played_at;
    }

    public function setPlayedAt(\DateTimeImmutable $played_at): static
    {
        $this->played_at = $played_at;

        return $this;
    }
}
