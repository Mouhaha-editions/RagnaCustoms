<?php

namespace App\Entity;

use App\Repository\SongRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @ORM\Entity(repositoryClass=SongRequestRepository::class)
 */
class SongRequest
{
    use TimestampableEntity;


    const STATE_ASKED = 1;
    const STATE_IN_PROGRESS = 5;
    const STATE_ENDED = 10;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $link;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="songRequests")
     * @ORM\JoinColumn(nullable=false)
     */
    private $requestedBy;

    /**
     * @ORM\ManyToMany(targetEntity=Utilisateur::class, inversedBy="currentlyMapped")
     */
    private $mapperOnIt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $author;


    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $state = self::STATE_ASKED;

    /**
     * @ORM\OneToMany(targetEntity=SongRequestVote::class, mappedBy="songRequest", orphanRemoval=true)
     */
    private $songRequestVotes;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $wantToBeNotified = true;
    public function __construct()
    {
        $this->mapperOnIt = new ArrayCollection();
        $this->songRequestVotes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestedBy(): ?Utilisateur
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?Utilisateur $requestedBy): self
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    /**
     * @return Collection|Utilisateur[]
     */
    public function getMapperOnIt(): Collection
    {
        return $this->mapperOnIt;
    }

    public function addMapperOnIt(Utilisateur $mapperOnIt): self
    {
        if (!$this->mapperOnIt->contains($mapperOnIt)) {
            $this->mapperOnIt[] = $mapperOnIt;
        }

        return $this;
    }

    public function removeMapperOnIt(Utilisateur $mapperOnIt): self
    {
        $this->mapperOnIt->removeElement($mapperOnIt);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getYoutubeEmbeded()
    {
        preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $this->getLink(), $matches);
        return $matches[1] ?? "";
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return Utilisateur|false
     */
    public function getMapper()
    {
        return $this->getMapperOnIt()->first();
    }


    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(?int $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection|SongRequestVote[]
     */
    public function getSongRequestVotes(): Collection
    {
        return $this->songRequestVotes;
    }

    public function addSongRequestVote(SongRequestVote $songRequestVote): self
    {
        if (!$this->songRequestVotes->contains($songRequestVote)) {
            $this->songRequestVotes[] = $songRequestVote;
            $songRequestVote->setSongRequest($this);
        }

        return $this;
    }

    public function removeSongRequestVote(SongRequestVote $songRequestVote): self
    {
        if ($this->songRequestVotes->removeElement($songRequestVote)) {
            // set the owning side to null (unless already changed)
            if ($songRequestVote->getSongRequest() === $this) {
                $songRequestVote->setSongRequest(null);
            }
        }

        return $this;
    }

    public function getWantToBeNotified(): ?bool
    {
        return $this->wantToBeNotified;
    }

    public function setWantToBeNotified(?bool $wantToBeNotified): self
    {
        $this->wantToBeNotified = $wantToBeNotified;

        return $this;
    }

}
