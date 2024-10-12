<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Entity\Vote;
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
        if ($vote->getFlow() > 0) {
            return ($vote->getFlow() + $vote->getLevelQuality() + $vote->getFunFactor() + $vote->getRhythm(
                    ) + $vote->getReadability() + $vote->getPatternQuality()) / 6;
        }
        return ($vote->getFunFactor() + $vote->getRhythm() + $vote->getReadability() + $vote->getPatternQuality()) / 4;
    }

    public function addScore(Song $song, Vote $vote)
    {
        $song->setTotalVotes($song->getTotalVotes() + $this->getMoyenne($vote));
        $song->setCountVotes($song->getCountVotes() + 1);
    }

    /**
     * add one upvote to the song, a remove one down vote if needed
     * @param  Song  $song
     * @param  UserInterface|null  $user
     */
    public function toggleUpVote(Song $song, ?UserInterface $user)
    {
        /**check if the user already voted this song (if he is in vote_counter table)*/
        $UserSongVoteCounter = $song->isVoteCounterBy($user);
        if ($UserSongVoteCounter != null) {
            /** vote exists */
            if (is_null($UserSongVoteCounter->getVotesIndc())) {
                /** vote was empty (user dismissed prompt from vote it box or previously voted and removed vote) */
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(true);
                /**update the vote up of the song */
                $song->setVoteUp($song->getVoteUp() + 1);
            } else if ($UserSongVoteCounter->getVotesIndc()) {
                /** vote is an upvote */
                /**update the voteCounter of the user/song to empty - avoids showing the song again in the vote it box */
                $UserSongVoteCounter->setVotesIndc(null);
                /**update the vote up of the song */
                $song->setVoteUp($song->getVoteUp() - 1);
            } else {
                /** vote is a downvote */
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(true);
                /**update the vote down and vote up of the song */
                $song->setVoteDown($song->getVoteDown() - 1);
                $song->setVoteUp($song->getVoteUp() + 1);
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
        if ($UserSongVoteCounter != null) {
            /** vote exists */
            if (is_null($UserSongVoteCounter->getVotesIndc())) {
                /** vote was empty (user dismissed prompt from vote it box or previously voted and removed vote) */
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(false);
                /**update the vote down of the song */
                $song->setVoteDown($song->getVoteDown() + 1);
            } else if ($UserSongVoteCounter->getVotesIndc()) {
                /** vote is an upvote */
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(false);
                /**update the vote down and vote up of the song */
                $song->setVoteDown($song->getVoteDown() + 1);
                $song->setVoteUp($song->getVoteUp() - 1);
            } else {
                /** vote is a downvote */
                /**update the voteCounter of the user/song to empty - avoids showing the song again in the vote it box */
                $UserSongVoteCounter->setVotesIndc(null);
                /**update the vote down of the song */
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

    /**
     * dismiss vote it box on home page by inserting empty vote counter
     * @param  Song  $song
     * @param  UserInterface|null  $user
     */
    public function dismissVote(Song $song, ?UserInterface $user)
    {
        /**check if the user already voted this song (if he is in vote_counter table)*/
        $UserSongVoteCounter = $song->isVoteCounterBy($user);

        /**
         * if user already voted on this song, then do nothing
         * if user never voted on this song, then create a new empty voteCounter for this user/song 
         */
        if ($UserSongVoteCounter == null) {
            /** vote does not exist */
            /** create a new VoteCounter */
            $newVoteCounter = new VoteCounter();
            $newVoteCounter->setSong($song);
            $newVoteCounter->setUser($user);
            $newVoteCounter->setVotesIndc(null);
            $song->addVoteCounter($newVoteCounter);
            $this->em->persist($newVoteCounter);
        }
        $this->em->flush();
    }

    public function canUpDownVote(Song $song, ?Utilisateur $user): bool
    {
        if ($user) {
            foreach ($song->getSongDifficulties() as $diff) {
                if ($user->hasPlayed($diff)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canReview(Song $song, ?Utilisateur $user): bool
    {
        if ($user) {
            foreach ($song->getSongDifficulties() as $diff) {
                if ($user->hasPlayed($diff)) {
                    return true;
                }
            }
        }

        return false;
    }
}

