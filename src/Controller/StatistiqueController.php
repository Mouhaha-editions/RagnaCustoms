<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Service\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatistiqueController extends AbstractController
{
    #[Route('/stats/scatter-score/{id}', name: 'app_stat_scatter_score')]
    public function byScore(Score $score, StatisticService $statisticService): JsonResponse
    {
        return new JsonResponse(['success'  => true,
                                 'datasets' => $statisticService->getScatterDataSetsByScore($score)
        ]);
    }

    #[Route('/stats/scatter-score-history/{id}', name: 'app_stat_scatter_score_history')]
    public function byScoreHistory(ScoreHistory $scoreHistory, StatisticService $statisticService): JsonResponse
    {
        return new JsonResponse(['success'  => true,
                                 'datasets' => $statisticService->getScatterDataSetsByScorehistory($scoreHistory)
        ]);
    }
}
