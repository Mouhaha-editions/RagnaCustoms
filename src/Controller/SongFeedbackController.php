<?php

namespace App\Controller;

use App\Entity\SongFeedback;
use App\Repository\SongFeedbackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SongFeedbackController extends AbstractController
{
    /**
     * @Route("/user/song/feedback", name="user_song_feedback")
     */
    public function index(SongFeedbackRepository $songFeedbackRepository): Response
    {
        return $this->render('song_feedback/index.html.twig', [
            'feedbacks' => $songFeedbackRepository->createQueryBuilder('f')->join('f.song', 's')
                ->where('s.user = :user')->setParameter('user', $this->getUser())->getQuery()->getResult(),
        ]);
    }
}
