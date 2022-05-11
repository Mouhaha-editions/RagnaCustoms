<?php

namespace App\Controller;

use App\Form\EvaluatorType;
use App\Service\SongService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ApplicationController extends AbstractController
{


    /**
     * @Route("/howto", name="howto")
     */
    public function howTo(): Response
    {
        return $this->render('howto/index.html.twig', [
            'controller_name' => 'ApplicationController',
        ]);
    }

    /**
     * @Route("/applications", name="applications")
     */
    public function menu(): Response
    {
        return $this->render('application/menu.html.twig', [
            'controller_name' => 'ApplicationController',
        ]);
    }

    /**
     * @Route("/application", name="application")
     */
    public function index(): Response
    {
        return $this->render('application/index.html.twig', [
            'controller_name' => 'ApplicationController',
        ]);
    }

    /**
     * @Route("/locale/{locale}", name="change_locale")
     */
    public function changeLocale(Request $request, string $locale, SessionInterface $session)
    {
        $session->set('_locale', $locale);
        if ($request->headers->get('referer') == null) {
            return $this->redirectToRoute('home');
        }
        return $this->redirect($request->headers->get('referer'));
    }


    /**
     * @Route("/map-evaluator", name="map_evaluator")
     */
    public function mapEvaluator(Request $request, SongService $songService)
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash("warning", "You need an account!");
            return $this->redirectToRoute("home");
        }
        $form = $this->createForm(EvaluatorType::class);
        $ranks = null;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $ranks = $songService->evaluateFile($form);
            }catch(\Exception $e){
                $this->addFlash('danger', $e->getMessage());
            }
        }
        return $this->render('application/map_evaluation.html.twig',[
            "form" => $form->createView(),
            "results"=>$ranks
        ]);
    }
}
