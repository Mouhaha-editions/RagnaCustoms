<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\RankedScoresRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=RankedScoresRepository::class)
 */
#[ApiResource(
    collectionOperations: [
        "get",
//        "post" => ["security" => "is_granted('ROLE_ADMIN')"],
    ],
    itemOperations: [
        "get",
//        "put" => ["security" => "is_granted('ROLE_ADMIN') or object.owner == user"],
    ])]
class RankedScores
{
    use TimestampableEntity;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="scores")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
    * @ORM\Column(type="float", nullable=true)
    */
    private $totalPPScore;

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
}
