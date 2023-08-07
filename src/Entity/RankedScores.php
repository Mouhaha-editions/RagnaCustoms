<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\RankedScoresRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Service\StatisticService;

#[ApiResource(
    collectionOperations: [
        "get",
//        "post" => ["security" => "is_granted('ROLE_ADMIN')"],
    ],
    itemOperations: [
        "get",
//        "put" => ["security" => "is_granted('ROLE_ADMIN') or object.owner == user"],
    ])]
#[ORM\Entity(repositoryClass: RankedScoresRepository::class)]
class RankedScores
{
    use TimestampableEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'scores')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\Column(type: 'float', nullable: true)]
    private $totalPPScore;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $plateform = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTotalPPScore(): ?float
    {
        return $this->totalPPScore;
    }

    public function setTotalPPScore(?float $totalPPScore): self
    {
        $this->totalPPScore = $totalPPScore;

        return $this;
    }

    public function getTimeAgoShort()
    {
      return StatisticService::dateDiplayerShort($this->updatedAt);
    }

    public function getPlateform(): ?string
    {
        return $this->plateform;
    }

    public function setPlateform(?string $plateform): static
    {
        $this->plateform = $plateform;

        return $this;
    }
}
