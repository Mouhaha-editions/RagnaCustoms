<?php


namespace App\Service;


use App\Entity\Utilisateur;

class MembershipService
{
    private GrantedService $grantedService;

    public function __construct(GrantedService $grantedService)
    {
        $this->grantedService = $grantedService;
    }

    public function  displayUsername(Utilisateur $user)
    {
        if($this->grantedService->isGranted($user,'ROLE_PREMIUM_LVL2')){
            return "<span style='color:".$user->getUsernameColor()."'>".$user->getUsername()."</span>";
        }else{
            return "<span style='color:#ffffff'>".$user->getUsername()."</span>";
        }
    }

}