<?php

namespace App\Controller\Backend;

use App\Entity\Challenge;
use App\Repository\ParticipationRepository;
use App\Repository\RunRepository;
use App\Service\ChallengeService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/leaderboard")
 */
class LeaderboardController extends AbstractController
{
    /**
     * @Route("/{id}", name="admin_leaderboard_index", methods={"GET"})
     * @param RunRepository $runRepository
     * @param ChallengeService $challengeService
     * @return Response
     */
    public function index(RunRepository $runRepository, ChallengeService $challengeService, Challenge $challenge = null): Response
    {
        if ($challenge === null) {
            $challenge = $challengeService->getRunningChallenge();
        }
        $runs = $runRepository->findByScore($challenge);
        $placed = new ArrayCollection();
        foreach ($runs as $i => $run) {
            if ($placed->contains($run->getUser()) || $placed->count() >= 10) {
                unset($runs[$i]);
                continue;
            }
            $placed->add($run->getUser());
        }

        return $this->render('backend/leaderboard/index.html.twig', [
            'runs' => $runs,
            'challenge' => $challenge,
        ]);
    }
}
