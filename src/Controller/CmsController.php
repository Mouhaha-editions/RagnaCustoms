<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CmsController extends AbstractController
{
    /**
     * @Route("/getting-started", name="getting_started")
     * @return Response
     */
    public function gettingStarted(): Response
    {
        return $this->render('cms/getting_started.html.twig');
    }

    /**
     * @Route("/ranking-system", name="ranking_system")
     * @return Response
     */
    public function rankingSystem(): Response
    {
        return $this->render('cms/ranking_system.html.twig');
    }

    /**
     * @Route("/acceptance-criteria", name="acceptance_criteria")
     * @return Response
     */
    public function acceptanceCriteria(): Response
    {
        return $this->render('cms/acceptance_criteria.html.twig');
    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function homepage(): Response
    {

        return $this->render('cms/homepage.html.twig');
    }

}
