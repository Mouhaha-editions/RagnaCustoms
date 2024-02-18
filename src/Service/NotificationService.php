<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Enum\ENotification;
use App\Repository\NotificationRepository;

readonly class NotificationService
{
    public function __construct(private NotificationRepository $notificationRepository)
    {
    }

    public function send(Utilisateur $utilisateur, string $message)
    {
        $notification = new Notification();
        $notification->setUser($utilisateur);
        $notification->setMessage($message);
        $this->notificationRepository->add($notification, true);
    }

}
