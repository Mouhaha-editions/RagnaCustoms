<?php

namespace App\Entity;

use App\Repository\DownloadCounterRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DownloadCounterRepository::class)
 */
class DownloadCounter
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Song::class, inversedBy="downloadCounters")
     */
    private $song;

    /**
     * @ORM\ManyToOne(targetEntity=Utilisateur::class, inversedBy="downloadCounters")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ip;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSong(): ?Song
    {
        return $this->song;
    }

    public function setSong(?Song $song): self
    {
        $this->song = $song;

        return $this;
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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }
}
