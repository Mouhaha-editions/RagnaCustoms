<?php

namespace App\Entity;

use App\Repository\FriendRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: FriendRepository::class)]
class Friend
{
    const STATE_REQUESTED = 1;
    const STATE_ACCEPTED = 2;
    const STATE_REFUSED = 3;
    const STATE_NOT_REQUESTED = 0;

    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'friendRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?utilisateur $user = null;

    #[ORM\ManyToOne(inversedBy: 'friends')]
    #[ORM\JoinColumn(nullable: false)]
    private ?utilisateur $friend = null;

    #[ORM\Column]
    private ?int $state = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?utilisateur
    {
        return $this->user;
    }

    public function setUser(?utilisateur $user): static
    {
        $this->user = $user;
        $this->user->addFriendRequest($this);
        return $this;
    }

    public function getFriend(): ?utilisateur
    {
        return $this->friend;
    }

    public function setFriend(?utilisateur $friend): static
    {
        $this->friend = $friend;
        $this->friend->addFriend($this);

        return $this;
    }

    public function getOther(UserInterface $user)
    {
        return $this->getUser() === $user ? $this->getFriend() : $this->getUser();
    }
    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): static
    {
        $this->state = $state;

        return $this;
    }
}
