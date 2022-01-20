<?php

namespace App\Controller;
use App\Entity\Song;
use App\Entity\VoteCounter;
use App\Service\VoteService;

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
    * @Route("/upFromMain/{id}", name="_upFromMain")
    */
    public function addUpVoteMain (VoteService $voteService, Song $song, TranslatorInterface $translator)
    {        
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $currentUser = $this->getUser();
            $voteService->addUpVote($song, $currentUser);
        }
        return $this->redirectToRoute("home");
    }


    /**
    * @Route("/downFromDetail/{id}", name="_downFromDetail")
    */
    public function addDownVoteDetail (VoteService $voteService, Song $song, TranslatorInterface $translator)
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $currentUser = $this->getUser();
            $voteService->addDownVote($song, $currentUser);
        }
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }

    /**
    * @Route("/downFromMain/{id}", name="_downFromMain")
    */
    public function addDownVoteMain (VoteService $voteService, Song $song, TranslatorInterface $translator)
    {
        // not connected
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $currentUser = $this->getUser();
            $voteService->addDownVote($song, $currentUser);
        }
        return $this->redirectToRoute("home");
    }

    /**
     * @Route("/deleteVoteFromDetail/{id}", name="_deleteVoteFromDetail")
     */
    public function deleteVoteDetail (VoteService $voteService, Song $song) {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $currentUser = $this->getUser();
            $voteService->deleteVote($song, $currentUser);
        }
        return $this->redirectToRoute("song_detail", ['slug' => $song->getSlug()], 301);
    }

    /**
     * @Route("/deleteVoteFromMain/{id}", name="_deleteVoteFromMain")
     */
    public function deleteVoteMain (VoteService $voteService, Song $song) {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("Login to vote"));
        } else {
            $currentUser = $this->getUser();
            $voteService->deleteVote($song, $currentUser);
        }
        return $this->redirectToRoute("home");
    }
}
