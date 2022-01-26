<?php

namespace App\Controller;

use App\Entity\Song;
use App\Entity\Vote;
use App\Entity\VoteCounter;
use App\Form\VoteType;
use App\Repository\VoteRepository;
use App\Service\DiscordService;
use App\Service\VoteService;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @Route("/song-vote", name="song_vote")
 */
class VotesController extends AbstractController
{

    /**
     * @Route("/upvote/{id}", name="_upvote")
     */
    public function toggleUpVote(VoteService $voteService, Song $song, TranslatorInterface $translator)
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $voteService->toggleUpVote($song, $this->getUser());
        }
        return new JsonResponse(['result' => $this->renderView('songs/partial/downupvote.html.twig', ['song' => $song,])]);
    }

    /**
     * @Route("/downvote/{id}", name="_downvote")
     */
    public function toggleDownVote(VoteService $voteService, Song $song, TranslatorInterface $translator)
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $voteService->toggleDownVote($song, $this->getUser());
        }
        return new JsonResponse(['result' => $this->renderView('songs/partial/downupvote.html.twig', ['song' => $song])]);
    }

    /**
     * @Route("/review/{id}", name="_review")
     * @param Request $request
     * @param Song $song
     * @param VoteRepository $voteRepository
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function songReview(Request $request, Song $song,
                               VoteRepository $voteRepository, TranslatorInterface $translator,
                               VoteService $voteService, DiscordService $discordService): Response
    {
        if ($song == null) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to vote !"),
                "response" => $translator->trans("Custom song not found !"),
            ]);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to vote !"),
                "response" => $this->renderView('songs/partial/detail_vote.html.twig', [
                    "song" => $song,
                    'message' => $translator->trans("You need an account to vote !")
                ])
            ]);
        }

        if ($song->getUser() == $this->getUser()) {
            return new JsonResponse([
                "error" => true,
                "errorMessage" => $translator->trans("You need an account to vote !"),
                "response" => $this->renderView('songs/partial/detail_vote.html.twig', [
                    "song" => $song,
                    'message' => $translator->trans("You can't review a custom song you've submitted")
                ])
            ]);
        }
        $vote = $voteRepository->findOneBy([
            'song' => $song,
            'user' => $this->getUser()
        ]);
        if ($vote == null) {
            $vote = new Vote();
            $vote->setUser($this->getUser());
            $vote->setSong($song);
        }
        $voteBefore = clone $vote;
        $form = $this->createForm(VoteType::class, $vote, [
            'method' => "post",
            'action' => $this->generateUrl('song_vote_review', ['id' => $song->getId()]),
            "attr" => [
                "class" => "form ajax-form",
                "data-url" =>  $this->generateUrl("song_vote_review", ['id' => $song->getId()])
            ]
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($vote->getId() != null) {
                $voteService->subScore($song, $voteBefore);
            }
            $vote->setLevelQuality(null);
            $vote->setFlow(null);
            $voteService->addScore($song, $vote);

            if($vote->getFeedback() != null && !empty($vote->getFeedback())){
                $discordService->sendFeedback($vote);
                $vote->setIsModerated(false);
            }
            $em->persist($vote);
            $em->flush();

            return new JsonResponse([
                "error" => false,
                "errorMessage" => false,
                "response" => $this->renderView("songs/partial/vote.html.twig", [
                    'song' => $song,
                    "vote" => $vote
                ]),
            ]);
        }
        return new JsonResponse([
            "error" => false,
            "errorMessage" => false,
            "response" => $this->renderView("songs/partial/form_review.html.twig", [
                'song' => $song,
                'form' => $form->createView(),
            ]),
        ]);
    }
}
