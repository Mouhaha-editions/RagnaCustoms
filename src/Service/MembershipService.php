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

    public function  displayUsername(Utilisateur $user, bool $withPrefix = true)
    {
        if($this->grantedService->isGranted($user,'ROLE_PREMIUM_LVL2')){
            return "<span title=\"Premium member\" style='color:".$user->getUsernameColor()."'>".($withPrefix ? "<i data-toggle='tooltip' title='Premium member' class='fas fa-gavel'></i> ":'').$user->getUsername()."</span>";
        }else{
            return "<span style='color:#ffffff'>".$user->getUsername()."</span>";
        }
    }

    public function  displayMappername(Utilisateur $user, bool $withPrefix = true)
    {
        if($this->grantedService->isGranted($user,'ROLE_PREMIUM_LVL2')){
            return "<span title='Premium member' style='color:".$user->getUsernameColor()."'>".($withPrefix ? "<i data-toggle='tooltip' title='Premium member' class='fas fa-gavel'></i> ":'').($user->getMapperName()??$user->getUsername())."</span>";
        }else{
            return "<span>".($user->getMapperName()??$user->getUsername())."</span>";
        }
    }

}