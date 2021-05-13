<?php

namespace App\Entity;

use App\Repository\ScoreRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScoreRepository::class)
 * @ORM\Table(name="score", uniqueConstraints={
 *  @ORM\UniqueConstraint(name="user_difficulty",
 *            columns={"user_id", "song_difficulty_id"})
 *     })
 */
class Score
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
     * @ORM\Column(type="float")
     */
    private $score;

    /**
     * @ORM\ManyToOne(targetEntity=SongDifficulty::class, inversedBy="scores")
     * @ORM\JoinColumn(nullable=false)
     */
    private $songDifficulty;

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
}
