<?php

namespace App\Controller;

use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Service\StatisticService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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

        $pages = [
            'Sessions' => [],
            //            'Hit Delta Times' => [],
        ];

        /** @var ScoreHistory $history */
        foreach ($histories as $history) {
            $pages['Sessions'][] = ([
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
//            $pages['Hit Delta Times'][] = array_merge([
//                'Song'      => $history->getSongDifficulty(),
//                'Plateform' => $history->getPlateform(),
//                'Date'       => $history->getCreatedAt()->format('Y-m-d H:i'),
//            ], json_decode(json_decode(($history->getExtra())))->HitDeltaTimes);
        }
        $spreadsheet = new Spreadsheet();
        $i = 0;
        foreach ($pages as $pageName => $data) {
            $sheet = $spreadsheet->createSheet($i);
            $sheet->setTitle($pageName);
            $row = 1;
            $i++;

            // Écrire les en-têtes de colonne
            $column = 1;
            foreach (array_keys($data[0]) as $header) {
                $cellAddress = $this->columnToLetter($column) . $row;
                $sheet->setCellValue($cellAddress, $header);
                $column++;
            }

            // Écrire les données
            $row++;
            foreach ($data as $item) {
                $column = 1;
                foreach ($item as $value) {
                    $cellAddress = $this->columnToLetter($column) . $row;
                    $sheet->setCellValue($cellAddress, $value);
                    $column++;
                }
                $row++;
            }

            // Ajuster la largeur des colonnes
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Csv($spreadsheet);
        ob_start();
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'data.csv'
        ));

        $writer->save('php://output');
        $response->setContent(ob_get_clean());

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
