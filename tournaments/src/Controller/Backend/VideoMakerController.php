<?php

namespace App\Controller\Backend;


use App\Entity\Challenge;
use App\Entity\Participation;
use App\Entity\User;
use App\Repository\ChallengeRepository;
use App\Repository\ParticipationRepository;
use App\Repository\RunRepository;
use App\Service\ChallengeService;
use App\Service\RunService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;
use TwitchApi\TwitchApi;

/**
 * @Route("/admin/video-maker")
 */
class VideoMakerController extends AbstractController
{
    const Game = "Mount & Blade II: Bannerlord";

    /**
     * @Route("/", name="video_maker_admin_new", methods={"GET","POST"})
     * @param Request $request
     * @param ChallengeService $challengeService
     * @param ParticipationRepository $participationRepository
     * @param ChallengeRepository $challengeRepository
     * @return Response
     * @throws \TwitchApi\Exceptions\ClientIdRequiredException
     * @throws \TwitchApi\Exceptions\InvalidLimitException
     * @throws \TwitchApi\Exceptions\InvalidTypeException
     * @throws \TwitchApi\Exceptions\TwitchApiException
     * @throws \TwitchApi\Exceptions\UnsupportedOptionException
     */
    public function current(Request $request, ChallengeService $challengeService, ParticipationRepository $participationRepository, ChallengeRepository $challengeRepository): Response
    {
        $options = [
            'client_id' => 'o7bf3gxsqjjaameb102lbkre19o32d',
        ];
        $challenge = $challengeService->getRunningChallenge();

        if ($challenge != null) {

            $participants = $participationRepository->findByChallenge($challenge);
            $clips = [];
            $twitchApi = new TwitchApi($options);

            $datesstart = $challenge->getChallengeDates()->first()->getStartDate();
            $datesend = $challenge->getChallengeDates()->last()->getEndDate();
//            $clips = array_merge($clips, $twitchApi->getTopClips(
//                "harcdcorechallengetv",
//                null,
//                "Mount & Blade II: Bannerlord",100,'month')['clips']
//
//            );
            foreach ($participants as $participant) {
                if ($participant->getUser()->getTwitchID() != null) {
                    try {
                        $clips = array_merge($clips, $twitchApi->getTopClips(
                            $participant->getUser()->getTwitchID(),
                            null,
                            self::Game, 200, 'week')['clips']

                        );
                    } catch (\Exception $e) {
                        $this->addFlash('danger', "Impossible de joindre le twitch de " . $participant->getUser()->getTwitchID());
                    }
                }
            }

            foreach ($clips as $i => $clip) {
                $date = new \DateTime($clip['created_at']);
                if ($date < $datesstart || $clip['game'] != self::Game) {
                    unset($clips[$i]);
                }
            }
//            VarDumper::dump($clips);

            return $this->render('backend/video_maker/index.html.twig', [
                'clips' => $clips
            ]);
        } else {
            return $this->render('backend/video_maker/selection_challenge.html.twig', [
                'challenges' => $challengeRepository->findBy([], ["id" => "asc"]),

            ]);
        }
    }

    /**
     * @Route("/{id}", name="video_maker_specifique_admin_new", methods={"GET","POST"})
     * @param Request $request
     * @param ChallengeService $challengeService
     * @param ParticipationRepository $participationRepository
     * @param ChallengeRepository $challengeRepository
     * @return Response
     * @throws \TwitchApi\Exceptions\ClientIdRequiredException
     * @throws \TwitchApi\Exceptions\InvalidLimitException
     * @throws \TwitchApi\Exceptions\InvalidTypeException
     * @throws \TwitchApi\Exceptions\TwitchApiException
     * @throws \TwitchApi\Exceptions\UnsupportedOptionException
     */
    public function currentSpecifique(Request $request, Challenge $challenge, ParticipationRepository $participationRepository, ChallengeRepository $challengeRepository): Response
    {
        $options = [
            'client_id' => 'o7bf3gxsqjjaameb102lbkre19o32d',
        ];

        $participants = $participationRepository->findByChallenge($challenge);
        $clips = [];
        $twitchApi = new TwitchApi($options);
        $datesstart = $challenge->getChallengeDates()->first()->getStartDate();
        $datesend = $challenge->getChallengeDates()->last()->getEndDate();
//        $clips = array_merge($clips, $twitchApi->getTopClips(
//            "harcdcorechallengetv",
//            null,
//            "Mount & Blade II: Bannerlord",100,'month')['clips']
//
//        );
        foreach ($participants as $participant) {
            if ($participant->getUser()->getTwitchID() != null) {
                try {
                    $cursor = 0;
                    while ($cursor < 5) {
                        $clips = array_merge($clips, $twitchApi->getTopClips(
                            $participant->getUser()->getTwitchID(),
                            $cursor, null )['clips']

                        );
                        $cursor++;
                    }
                } catch (\Exception $e) {
                    $this->addFlash('danger', "Impossible de joindre le twitch de " . $participant->getUser()->getTwitchID());
                }
            }
        }
        foreach ($clips as $i => $clip) {
            $date = new \DateTime($clip['created_at']);
            if ($date < $datesstart || $clip['game'] != self::Game) {
                unset($clips[$i]);
            }
        }
        return $this->render('backend/video_maker/index.html.twig', [
            'clips' => $clips
        ]);

    }

}
