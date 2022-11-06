<?php

namespace App\Controller;

use App\Entity\Song;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Service\DiscordService;
use App\Service\RankingScoreService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class RankingSongsController extends AbstractController
{
    private $paginate = 30;

    /**
     * @Route("/ranking-song/", name="ranking_song")
     */
    public function library(Request $request, DiscordService $discordService, SongDifficultyRepository $songDifficultyRepository, RankingScoreService $rankingScoreService): Response
    {
        $form = $this->createFormBuilder();
        $form->add('songs', EntityType::class, [
            'class'         => Song::class,
            'multiple'      => true,
            'choice_label'  => function (Song $song) {
                return ($song->isRanked() ? "[R] " : "") . $song->getName();
            },
            "attr"          => [
                'class' => "select2"
            ],
            "query_builder" => function (SongRepository $er) {
                return $er->createQueryBuilder('s')
                          ->orderBy("s.name", "ASC");
            }
        ]);
        $form->add('rank_unrank', SubmitType::class);
        $form = $form->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Song $song */
            $alreadyRanked = false;
            foreach ($form->get('songs')->getData() as $song) {
                foreach ($song->getSongDifficulties() as $diff) {
                    if ($diff->isRanked()) {
                        $alreadyRanked = true;
                    }
                    $diff->setIsRanked(true);//!$diff->isRanked());
                    $songDifficultyRepository->add($diff, true);
                }
                if (!$alreadyRanked) {
                    $discordService->rankedSong($song);
                }
                $rankingScoreService->calculateForSong($song);
            }
            $this->addFlash('success', 'Songs ranked or unranked');
            return $this->redirectToRoute('ranking_song');
        }

        return $this->renderForm('ranking_song/index.html.twig', [
            'form' => $form
        ]);
    }

    /**
     * @Route("/ranking-song/recalculate", name="calculate_all")
     */
    public function calculateAll(RankingScoreService $rankingScoreService)
    {
        $rankingScoreService->calculateAll();
        return $this->redirectToRoute('ranking_song');

    }


}
