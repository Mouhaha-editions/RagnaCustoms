<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\SongCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    collectionOperations: [
        "get",
//        "post" => ["security" => "is_granted('ROLE_ADMIN')"],
    ],
    itemOperations: [
        "get",
//        "put" => ["security" => "is_granted('ROLE_ADMIN') or object.owner == user"],
    ])]
#[ORM\Entity(repositoryClass: SongCategoryRepository::class)]
class SongCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\Column(type: 'boolean')]
    private $isFeedbackable;

    #[ORM\Column(type: 'boolean')]
    private $isReviewable;


    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isOnlyForAdmin;

    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'categoryTags')]
    private $songs;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
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

    public function getIsFeedbackable(): ?bool
    {
        return $this->isFeedbackable;
    }

    public function setIsFeedbackable(bool $isFeedbackable): self
    {
        $this->isFeedbackable = $isFeedbackable;

        return $this;
    }

    public function getIsReviewable(): ?bool
    {
        return $this->isReviewable;
    }

    public function setIsReviewable(bool $isReviewable): self
    {
        $this->isReviewable = $isReviewable;

        return $this;
    }


    public function getIsOnlyForAdmin(): ?bool
    {
        return $this->isOnlyForAdmin;
    }

    public function setIsOnlyForAdmin(?bool $isOnlyForAdmin): self
    {
        $this->isOnlyForAdmin = $isOnlyForAdmin;

        return $this;
    }

    /**
     * @return Collection|Song[]
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): self
    {
        if (!$this->songs->contains($song)) {
            $this->songs[] = $song;
            $song->addCategoryTag($this);
        }

        return $this;
    }

    public function removeSong(Song $song): self
    {
        if ($this->songs->removeElement($song)) {
            $song->removeCategoryTag($this);
        }

        return $this;
    }

    public function isIsFeedbackable(): ?bool
    {
        return $this->isFeedbackable;
    }

    public function isIsReviewable(): ?bool
    {
        return $this->isReviewable;
    }

    public function isIsOnlyForAdmin(): ?bool
    {
        return $this->isOnlyForAdmin;
    }

}
