<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Service\StatisticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class StatistiqueController extends AbstractController
{
    #[Route('/stats/scatter-score/{id}', name: 'app_stat_scatter_score')]
    public function byScore(Score $score, StatisticService $statisticService): JsonResponse
    {
        return new JsonResponse([
            'success'  => true,
            'datasets' => $statisticService->getScatterDataSetsByScore($score)
        ]);
    }

    #[Route('/stats/scatter-score-history/{id}', name: 'app_stat_scatter_score_history')]
    public function byScoreHistory(ScoreHistory $scoreHistory, StatisticService $statisticService): JsonResponse
    {
        return new JsonResponse([
            'success'  => true,
            'datasets' => $statisticService->getScatterDataSetsByScorehistory($scoreHistory)
        ]);
    }

    #[Route('/stats/download/{id}', name: 'app_download_score_history')]
    public function downloadScoreHistory(Song $song, StatisticService $statisticService): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $histories = $user->getScoreHistories()->filter(function (ScoreHistory $scoreHistory) use ($song) {
            return $scoreHistory->getSongDifficulty()->getSong() === $song;
        });

        $page = [];

        /** @var ScoreHistory $history */
        foreach ($histories as $history) {
            $page[] = ([
                'Song'            => $history->getSongDifficulty(),
                'Plateform'       => $history->getPlateform(),
                'Date'            => $history->getCreatedAt()->format('Y-m-d H:i'),
                'Distance'        => $history->getScoreDisplay(),
                'Score'           => $history->getRawPP(),
                'Half combo'      => $history->getComboBlue(),
                'Full combo'      => $history->getComboYellow(),
                'Hit'             => $history->getHit(),
                'Missed'          => $history->getMissed(),
                'Hit Delta Times' => json_encode($statisticService->getFullDatasetByScorehistory($history))
            ]);
        }
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $song->getSlug() . '.csv"');
        $response->setCallback(function () use ($page) {
            $output = fopen('php://output', 'w');

            // Écrire l'en-tête CSV
            fputcsv($output, array_keys($page[0]));

            // Écrire les lignes de données
            foreach ($page as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        });

        return $response;
    }


    // Fonction pour convertir le numéro de colonne en notation alphabétique
    private function columnToLetter($column)
    {
        $letter = '';
        while ($column > 0) {
            $column--;
            $letter = chr($column % 26 + 65) . $letter;
            $column = intval($column / 26);
        }
        return $letter;
    }

}
