<?php

namespace App\Controller;

use App\Repository\SongRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ScoreController extends AbstractController
{
    /**
     * @Route("/leaderboard", name="score")
     */
    public function index(SongRepository $songRepository): Response
    {
        $songs = $songRepository->createQueryBuilder('s')
            ->where('s.moderated = true')
            ->orderBy('s.name','ASC')->getQuery()->getResult();


        return $this->render('score/index.html.twig', [
            'songs'=>$songs
        ]);
    }
}
