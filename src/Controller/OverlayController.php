<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OverlayController extends AbstractController
{
    /**
     * @Route("/overlay/display/{api}", name="overlay")
     */
    public function index(string $api): Response
    {
        return $this->render('overlay/index.html.twig', [
            'controller_name' => 'OverlayController',
            'apiKey'=>$api
        ]);
    }

    /**
     * @Route("/overlay/details/{api}", name="overlay_details")
     */
    public function detail($apikey): Response
    {
        return $this->render('overlay/index.html.twig', [
            'controller_name' => 'OverlayController',
        ]);
    }
}
