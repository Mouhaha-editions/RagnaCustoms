<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="admin_index", methods={"GET"})
     * @return Response
     */
    public function index(): Response
    {
        if(!$this->isGranted('ROLE_ASSO')) {
            return $this->render('backend/default/empty_dashboard.html.twig');
        }

        return $this->render('backend/default/dashboard.html.twig', [

        ]);
    }
}
