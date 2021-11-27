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

    public function __construct()
    {
        $this->mapperOnIt = new ArrayCollection();
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
        return $matches[1];
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
        VarDumper::dump($this->getMapperOnIt()->first());
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

}
