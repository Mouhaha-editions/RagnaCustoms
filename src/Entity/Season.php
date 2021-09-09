<?php

namespace App\Entity;

use App\Repository\SeasonRepository;
use Carbon\Doctrine\DateTimeType;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=SeasonRepository::class)
 */
class Season
{
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
     * @ORM\Column(type="datetime")
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endDate;

    /**
     * @ORM\OneToMany(targetEntity=Score::class, mappedBy="season")
     */
    private $scores;

    /**
     * @ORM\ManyToMany(targetEntity=SongDifficulty::class, inversedBy="seasons")
     */
    private $difficulties;

    /**
     * @Gedmo\Slug(fields={"label"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $slug;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
        $this->difficulties = new ArrayCollection();
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

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function isActive()
    {
        return $this->getStartDate() <= new DateTime() && $this->getEndDate() >= new DateTime();
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
            $score->setSeason($this);
        }

        return $this;
    }

    public function removeScore(Score $score): self
    {
        if ($this->scores->removeElement($score)) {
            // set the owning side to null (unless already changed)
            if ($score->getSeason() === $this) {
                $score->setSeason(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * @return Collection|SongDifficulty[]
     */
    public function getDifficulties(): Collection
    {
        return $this->difficulties;
    }

    public function addDifficulty(SongDifficulty $difficulty): self
    {
        if (!$this->difficulties->contains($difficulty)) {
            $this->difficulties[] = $difficulty;
            $difficulty->addSeason($this);
        }

        return $this;
    }

    public function removeDifficulty(SongDifficulty $difficulty): self
    {
        $this->difficulties->removeElement($difficulty);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
