<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\ScoreRepository;
use App\Repository\SongDifficultyRepository;
use App\Service\RankingScoreService;
use App\Service\ScoreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PowerPointController extends AbstractController
{
    #[Route('/pp', name: 'app_power_point')]
    public function index(
        Request $request,
        SongDifficultyRepository $songDifficultyRepository,
        ScoreRepository $scoreRepository,
        ScoreService $scoreService,
        RankingScoreService $rankingScoreService
    ): Response {
        $played = [];
        $notPlayed = [];
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $diffs = $songDifficultyRepository->getRanked();
            foreach ($diffs as $diff) {
                if ($user->playedPlateform($diff,$request->get('vr',false))) {
                    $played[] = $diff;
                } else {
                    $notPlayed[] = $diff;
                }
            }

        return $this->render('power_point/index.html.twig', [
            'played' => $played,
            'user' => $this->getUser(),
            'notPlayed' => $notPlayed,
        ]);
    }
}
