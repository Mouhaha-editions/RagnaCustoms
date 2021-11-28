<?php

namespace App\Controller\Backend;

use App\Entity\Challenge;
use App\Entity\Participation;
use App\Repository\ChallengeRepository;
use App\Repository\ParticipationRepository;
use App\Service\ChallengeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/caster")
 */
class CasterController extends AbstractController
{
    /**
     * @Route("/", name="caster_index", methods={"GET"})
     * @param ParticipationRepository $participationRepository
     * @param ChallengeService $challengeService
     * @param ChallengeRepository $challengeRepository
     * @return Response
     */
    public function index(ParticipationRepository $participationRepository, ChallengeService $challengeService, ChallengeRepository $challengeRepository): Response
    {
        $challenge = $challengeService->getRunningChallenge();

        if($challenge != null) {
            /** @var Participation[] $participations */
            $participations = $participationRepository->findByChallenge($challenge);


            return $this->render('backend/caster/index.html.twig', [
                'participations' => $participations,
                'challenge' => $challenge,

            ]);
        }else{

            return $this->render('backend/caster/selection_challenge.html.twig', [
                'challenges' => $challengeRepository->findBy([], [
                    'season' => 'DESC',
                    'registrationOpening' => "DESC"
                ]),
            ]);
        }
    }

    /**
     * @Route("/{id}", name="caster_specifique_index", methods={"GET"})
     * @param ParticipationRepository $participationRepository
     * @param ChallengeService $challengeService
     * @return Response
     */
    public function indexSpecifique(ParticipationRepository $participationRepository, Challenge $challenge): Response
    {
        /** @var Participation[] $participations */
        $participations = $participationRepository->findByChallenge($challenge);

        return $this->render('backend/caster/index.html.twig', [
            'participations' => $participations,
            'challenge' => $challenge,

        ]);
    }
}
