<?php

namespace App\Controller;

use App\Service\TwitchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwitchController extends AbstractController
{
    /**
     * @Route("/twitch", name="twitch")
     */
    public function index(TwitchService $twitchService): Response
    {
        return $this->render('twitch/index.html.twig', [
            'controller_name' => 'TwitchController',
            'channels'=>$twitchService->getCurrentStreamindChannels()
        ]);
    }
}
