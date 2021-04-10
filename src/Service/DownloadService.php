<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class DownloadService
{
    private $security;
    protected $requestStack;

    public function __construct(Security $security, RequestStack $requestStack)
    {
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function alreadyDownloaded(Song $song) : bool
    {
        /** @var Utilisateur $user */
        $user = $this->security->getUser();
        if($user != null && $this->security->isGranted('ROLE_USER')){
            foreach($user->getDownloadCounters() AS $downloadCounter){
                if($downloadCounter->getSong() === $song){
                    return true;
                }
            }
        }
        $ip = $this->requestStack->getCurrentRequest()->getClientIp();
        foreach($song->getDownloadCounters() AS $downloadCounter){
            if($downloadCounter->getIp() === $ip){
                return true;
            }
        }

        return false;
    }
}

