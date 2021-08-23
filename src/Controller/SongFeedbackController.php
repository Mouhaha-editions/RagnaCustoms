<?php

namespace App\Controller;

use App\Entity\SongFeedback;
use App\Entity\Utilisateur;
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
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $hashes = [];
        foreach($user->getSongs() AS $song){
            $hashes = array_merge($hashes,$song->getHashes());
        }

        return $this->render('song_feedback/index.html.twig', [
            'feedbacks' => $songFeedbackRepository->createQueryBuilder('f')
                ->where('f.hash IN (:hashes)')
                ->setParameter('hashes', $hashes)
        ]);
    }
}
