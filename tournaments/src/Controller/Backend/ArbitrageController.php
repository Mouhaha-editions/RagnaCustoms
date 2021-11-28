<?php

namespace App\Controller\Backend;

use App\Entity\Challenge;
use App\Repository\ChallengeRepository;
use App\Repository\ParticipationRepository;
use App\Service\ChallengeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/arbitrage")
 */
class ArbitrageController extends AbstractController
{
    /**
     * @Route("/", name="arbitrage_index", methods={"GET"})
     * @param ParticipationRepository $participationRepository
     * @param ChallengeService $challengeService
     * @param ChallengeRepository $challengeRepository
     * @return Response
     */
    public function index(ParticipationRepository $participationRepository, ChallengeService $challengeService, ChallengeRepository $challengeRepository): Response
    {
        $challenge = $challengeService->getRunningChallenge();
        if ($challenge != null) {
            $participations = $participationRepository->findByChallenge($challenge);
            $twitch = [];
            foreach ($participations as $participation) {
                if ($participation->getUser()->getTwitchID() != null) {
                    $twitch[] = $participation->getUser()->getTwitchID();
                }
            }

            return $this->render('backend/arbitrage/index.html.twig', [
                'participations' => $participations,
                'allTwitch' => $twitch,
                'challenge' => $challenge,
            ]);
        } else {
            return $this->render('backend/arbitrage/selection_challenge.html.twig', [
                'challenges' => $challengeRepository->findBy([], [
                    'season' => 'DESC',
                    'registrationOpening' => "DESC"
                ]),

            ]);
        }
    }

    /**
     * @Route("/{id}", name="arbitrage_specifique_index", methods={"GET"})
     * @param ParticipationRepository $participationRepository
     * @param Challenge $challenge
     * @return Response
     */
    public function indexSpecifique(ParticipationRepository $participationRepository, Challenge $challenge): Response
    {
        $participations = $participationRepository->findByChallenge($challenge);
        $twitch = [];
        foreach ($participations as $participation) {
            $twitchId = $participation->getUser()->getTwitchID();
            if ($twitchId != null && !in_array($twitchId, $twitch)) {
                $twitch[] = $twitchId;
            }
        }
        return $this->render('backend/arbitrage/index.html.twig', [
            'participations' => $participations,
            'allTwitch' => $twitch,
            'challenge' => $challenge
        ]);
    }
}
