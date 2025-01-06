<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\ScoreRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongDifficultyRepository;
use App\Service\DiscordService;
use App\Service\RankingScoreService;
use App\Service\SearchService;
use App\Service\SongService;
use Doctrine\Persistence\ManagerRegistry;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class RankingSongsController extends AbstractController
{
    private $paginate = 30;

    #[Route(path: '/ranking-song/', name: 'ranking_song')]
    public function library(
        Request $request,
        ManagerRegistry $doctrine,
        PaginationService $paginationService,
        SearchService $searchService,
        SongCategoryRepository $categoryRepository,
    ): Response {
        $qb = $doctrine
            ->getRepository(Song::class)
            ->createQueryBuilder('song')
            ->addSelect('song.voteUp - song.voteDown AS HIDDEN rating')
            ->groupBy("song.id");

        $qb->leftJoin('song.songDifficulties', 'song_difficulties');

        $filters = $searchService->baseSearchQb($qb, $request);
        $pagination = $paginationService->setDefaults($this->paginate)->process($qb, $request);

        $categories = $categoryRepository
            ->createQueryBuilder("c")
            ->leftJoin("c.songs", 's')
            ->where('s.id is not null')
            ->orderBy('c.label')
            ->getQuery()
            ->getResult();

        return $this->render('ranking_song/index.html.twig', [
            'songs' => $pagination,
            'filters' => $filters,
            'categories' => $categories,
        ]);
    }

    #[Route(path: '/ranking-song/{id}/rank', name: 'ranking_song_rank')]
    public function rankSong(
        Request $request,
        Song $song,
        DiscordService $discordService,
        SongDifficultyRepository $songDifficultyRepository,
        SongService $songService,
        RankingScoreService $rankingScoreService
    ): JsonResponse {
        $form = $this->createFormBuilder();
        $form->setAction($this->generateUrl('ranking_song_rank', ['id' => $song->getId()]));
        foreach ($song->getSongDifficulties() as $i => $diff) {
            $form->add('accuracy_'.($i + 1), NumberType::class, [
                'attr' => [
                    'min' => 60,
                    'max' => 90
                ],
                'data' => $diff->getEstAvgAccuracy(),
                'label' => 'Avg accuracy'
            ]);
            $form->add('leaderboard_'.($i + 1), ChoiceType::class, [
                'choices' => [
                    'VR' => 'vr',
                    'VoT Classic' => 'flat',
                    'VoT OKOD' => 'okod',
                ],
                'label' => 'Preview for:',
                'mapped' => false
            ]);
        }
        $form->add('rank', SubmitType::class);
        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                return new JsonResponse([
                    'error' => true,
                    'errorMessage' => 'Invalid data, please check the form',
                    'response' => $this->renderView('ranking_song/partial/form_rank_song.html.twig', [
                        'form' => $form->createView(),
                        'song' => $song,
                        "error" => 'Invalid data, please check the form',
                    ]),
                ]);
            }
            $alreadyRanked = false;
            foreach ($song->getSongDifficulties() as $i => $diff) {
                if ($diff->isRanked()) {
                    $alreadyRanked = true;
                }

                $avgAccuracy = $form->get('accuracy_'.($i + 1))->getData();

                $diff->setIsRanked(true);
                if ($avgAccuracy) {
                    $diff->setEstAvgAccuracy($avgAccuracy);
                    $diff->setPPCurveMax($songService->calculatePPCurveMax($diff));
                } else {
                    $diff->setEstAvgAccuracy($diff->getEstAvgAccuracy());
                    $diff->setPPCurveMax($diff->getPPCurveMax());
                }

                $songDifficultyRepository->add($diff, true);
            }

            if (!$alreadyRanked) {
                $discordService->rankedSong($song);
            }

            $rankingScoreService->calculateForSong($song);

            $this->addFlash('success', 'Song ranked');

            return new JsonResponse([
                'error' => false,
                'goto' => $this->generateUrl('ranking_song'),
                'reload' => true,
                'errorMessage' => null,
            ]);
        }

        return new JsonResponse([
            'error' => false,
            'errorMessage' => "",
            'response' => $this->renderView('ranking_song/partial/form_rank_song.html.twig', [
                'form' => $form->createView(),
                'song' => $song,
                "error" => null,
            ]),
        ]);
    }

    #[Route(path: '/ranking-song/{id}/unrank', name: 'ranking_song_unrank')]
    public function unrankSong(
        Song $song,
        DiscordService $discordService,
        ScoreRepository $scoreRepository,
        SongDifficultyRepository $songDifficultyRepository,
        RankingScoreService $rankingScoreService
    ): JsonResponse {
        $alreadyRanked = false;
        foreach ($song->getSongDifficulties() as $diff) {
            if ($diff->isRanked()) {
                $alreadyRanked = true;
            }
            $diff->setIsRanked(false);
            $songDifficultyRepository->add($diff, true);
            foreach ($diff->getScores() as $score) {
                $score->setRawPP(null);
                $score->setWeightedPP(null);
                $scoreRepository->add($score);
                $rankingScoreService->calculateTotalPondPPScore($score->getUser(), $score->isVR(), $score->isOKODO());
            }
        }

        if ($alreadyRanked) {
            $discordService->rankedSong($song);
        }

        $this->addFlash('success', 'Song unranked');

        return new JsonResponse(['success' => true]);
    }
}
