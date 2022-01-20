<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\Vote;
use App\Entity\Utilisateur;
use App\Entity\VoteCounter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VoteService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em)
    {
        $this->kernel = $kernel;
        $this->em = $em;
    }

    public function subScore(Song $song, Vote $vote)
    {
        if ($vote->getId() != null) {
            $song->setTotalVotes($song->getTotalVotes() - $this->getMoyenne($vote));
            $song->setCountVotes($song->getCountVotes() - 1);
        }
    }

    private function getMoyenne(Vote $vote)
    {
        $total = $vote->getFlow() +
            $vote->getFunFactor() +
            $vote->getRhythm() +
            $vote->getReadability() +
            $vote->getLevelQuality() +
            $vote->getPatternQuality();
        return $total / 6;
    }

    public function addScore(Song $song, Vote $vote)
    {
        $song->setTotalVotes($song->getTotalVotes() + $this->getMoyenne($vote));
        $song->setCountVotes($song->getCountVotes() + 1);

    }

    /**
     * add one upvote to the song, a remove one down vote if needed
     * @param Song $song
     * @param UserInterface|null $user
     */
    public function toggleUpVote(Song $song, ?UserInterface $user)
    {

        /**check if the user already voted this song (if he is in vote_counter table)*/
        $UserSongVoteCounter = $song->isVoteCounterBy($user);

        /** if user already voted this song, then do nothing if the vote is the same. Else update the vote
         * if user never voted this song, then create a new voteCounter for this user/song and add one vote
         */
        if ($UserSongVoteCounter != null) {
            /** vote exists */
            if (!$UserSongVoteCounter->getVotesIndc()) {
                /** vote is an downvote */
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(true);
                /**update the vote down and vote up of the song */
                $song->setVoteDown($song->getVoteDown() - 1);
                $song->setVoteUp($song->getVoteUp() + 1);
            } else {
                /** vote is a upvote */
                $this->em->remove($UserSongVoteCounter);
                $song->setVoteUp($song->getVoteUp() - 1);
            }
        } else {
            /** vote does not exist */
            /** create a new VoteCounter */
            $newVoteCounter = new VoteCounter();
            $newVoteCounter->setSong($song);
            $newVoteCounter->setUser($user);
            $newVoteCounter->setVotesIndc(true);
            $song->addVoteCounter($newVoteCounter);
            $this->em->persist($newVoteCounter);
            /**update the vote down of the song */
            $song->setVoteUp($song->getVoteUp() + 1);
        }
        $this->em->flush();
    }

    /**
     * toggle down vote to the song and remove one upvote if needed
     */
    public function toggleDownVote(Song $song, ?UserInterface $user)
    {
        /**check if the user already voted this song (if he is in vote_counter table)*/
        $UserSongVoteCounter = $song->isVoteCounterBy($user);

        /** if user already voted this song, then do nothing if the vote is the same. Else update the vote
         * if user never voted this song, then create a new voteCounter for this user/song and add one vote
         */
        if ($UserSongVoteCounter != null) {
            /** vote exists */
            if ($UserSongVoteCounter->getVotesIndc()) {
                /** vote is an upvote */
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(false);
                /**update the vote down and vote up of the song */
                $song->setVoteDown($song->getVoteDown() + 1);
                $song->setVoteUp($song->getVoteUp() - 1);
            } else {
                /** vote is a downvote */
                $this->em->remove($UserSongVoteCounter);
                $song->setVoteDown($song->getVoteDown() - 1);
            }
        } else {
            /** vote does not exist */
            /** create a new VoteCounter */
            $newVoteCounter = new VoteCounter();
            $newVoteCounter->setSong($song);
            $newVoteCounter->setUser($user);
            $newVoteCounter->setVotesIndc(false);
            $song->addVoteCounter($newVoteCounter);
            $this->em->persist($newVoteCounter);
            /**update the vote down of the song */
            $song->setVoteDown($song->getVoteDown() + 1);
        }
        $this->em->flush();
    }

}

