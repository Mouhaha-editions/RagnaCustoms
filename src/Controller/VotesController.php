<?php

namespace App\Controller;
use App\Entity\Song;
use App\Entity\VoteCounter;
use App\Service\VoteService;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @Route("/song-vote", name="song_vote")
 */
class VotesController extends AbstractController
{
  /**
    * @Route("/upFromDetail/{id}", name="_upFromDetail")
    */
    public function addUpVoteDetail (VoteService $voteService, Song $song, TranslatorInterface $translator)
    {        
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $currentUser = $this->getUser();
            $voteService->addUpVote($song, $currentUser);
        }
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }

    /**
    * @Route("/upvote/{id}", name="_upvote")
    */
    public function addUpVoteMain (VoteService $voteService, Song $song, TranslatorInterface $translator)
    {        
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $voteService->toggleUpVote($song, $this->getUser());
        }
        return new JsonResponse(['result'=>$this->renderView('songs/partial/downupvote.html.twig',['song'=>$song,])]);
    }

    /**
    * @Route("/downvote/{id}", name="_downvote")
    */
    public function toggleDownVote (VoteService $voteService, Song $song, TranslatorInterface $translator)
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $voteService->toggleDownVote($song, $this->getUser());
        }
        return new JsonResponse(['result'=>$this->renderView('songs/partial/downupvote.html.twig',['song'=>$song])]);
    }


}
