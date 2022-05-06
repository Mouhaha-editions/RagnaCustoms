<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class MapperController extends AbstractController
{


    /**
     * @Route("/mappers", name="mappers")
     */
    public function list(Request $request,UtilisateurRepository $utilisateurRepository, PaginationService $paginationService): Response
    {
        /** @var Utilisateur[] $mappers */
        $qb = $utilisateurRepository->createQueryBuilder("u")
            ->leftJoin('u.songs', 's')
            ->select('u,COUNT(s) AS HIDDEN count_song')
            ->where('u.isMapper = 1')
            ->andWhere('s.id IS NOT NULL')
            ->where('s.isDeleted = 0')
            ->orderBy('count_song', 'desc')
            ->groupBy("u.id")
            ;

        $mappers = $paginationService->setDefaults(50)->process($qb,$request);
        return $this->render('mapper/index.html.twig', [
            'mappers' => $mappers,
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
}
