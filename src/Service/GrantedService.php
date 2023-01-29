<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class GrantedService
{
    private $accessDecisionManager;

    /**
     * Constructor
     *
     * @param AccessDecisionManagerInterface $accessDecisionManager
     */
    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    public function isGranted(?Utilisateur $user, $attributes, $object = null)
    {
        if($user === null)return false;
        if (!is_array($attributes))
            $attributes = [$attributes];
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        return ($this->accessDecisionManager->decide($token, $attributes, $object));
    }
}