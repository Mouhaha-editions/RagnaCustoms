<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\Vote;
use App\Entity\Utilisateur;
use App\Entity\VoteCounter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class VoteService
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em)
    {
        $this->kernel = $kernel;
        $this->em = $em;
    }

    public function subScore(Song $song, Vote $vote)
    {
        if ($vote->getId() != null) {
            $song->setTotalVotes($song->getTotalVotes() - $this->getMoyenne($vote));
            $song->setCountVotes($song->getCountVotes()-1);
        }
    }

    public function addScore(Song $song, Vote $vote)
    {
        $song->setTotalVotes($song->getTotalVotes() + $this->getMoyenne($vote));
        $song->setCountVotes($song->getCountVotes()+1);

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

    /**
     * add one upvote to the song, a remove one down vote if needed
     * @param Song $song
     * @param Utilisateur $user
     */
    public function addUpVote (Song $song, ?Utilisateur $user)
    {

        /**check if the user already voted this song (if he is in vote_counter table)*/
        $UserSongVoteCounter = $this->em->getRepository(VoteCounter::class)->findOneBy([
            'user' => $user,
            'song' => $song
        ]);
        
        /** if user already voted this song, then do nothing if the vote is the same. Else update the vote 
         * if user never voted this song, then create a new voteCounter for this user/song and add one vote
        */
        if ($UserSongVoteCounter != null) {
            if (!$UserSongVoteCounter->getVotesIndc()) {
                 
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(true);
                $this->em->persist($UserSongVoteCounter);
                $this->em->flush();

                /**update the vote down and vote up of the song */
                $song->setVoteUp($song->getVoteUp() + 1);
                $song->setVoteDown($song->getVoteDown() - 1);
            }
        } else {

            /**create a new VoteCounter */
            $newVoteCounter = new VoteCounter();
            $newVoteCounter->setSong($song);
            $newVoteCounter->setUser($user);
            $newVoteCounter->setVotesIndc(true);
            $this->em->persist($newVoteCounter);


            /**update the vote up of the song */
            $song->setVoteUp($song->getVoteUp() + 1);
            $this->em->persist($song);

            $this->em->flush();
        }
    }

    /**
     * add one down vote to the song and remove one upvote if needed
     */
    public function addDownVote (Song $song, ?Utilisateur $user)
    {

        /**check if the user already voted this song (if he is in vote_counter table)*/
        $UserSongVoteCounter = $this->em->getRepository(VoteCounter::class)->findOneBy([
            'user' => $user,
            'song' => $song
        ]); 

        /** if user already voted this song, then do nothing if the vote is the same. Else update the vote 
         * if user never voted this song, then create a new voteCounter for this user/song and add one vote
        */
        if ($UserSongVoteCounter != null) {
            if ($UserSongVoteCounter->getVotesIndc()) {
                
                /**update the voteCounter of the user/song */
                $UserSongVoteCounter->setVotesIndc(false);
                $this->em->persist($UserSongVoteCounter);
                
                /**update the vote down and vote up of the song */
                $song->setVoteDown($song->getVoteDown() + 1);
                $song->setVoteUp($song->getVoteUp() - 1);
                $this->em->persist($song);

                $this->em->flush();
            }
        } else {
            /**create a new VoteCounter */
            $newVoteCounter = new VoteCounter();
            $newVoteCounter->setSong($song);
            $newVoteCounter->setUser($user);
            $newVoteCounter->setVotesIndc(false);
            $this->em->persist($newVoteCounter);

            /**update the vote down of the song */
            $song->setVoteDown($song->getVoteDown() + 1);
            $this->em->persist($song);
            $this->em->flush();
        }
    }

    /**
     * Delete the vote of the user
     */
    public function deleteVote (Song $song, ?Utilisateur $user)
    {
        $UserSongVoteCounter = $this->em->getRepository(VoteCounter::class)->findOneBy([
            'user' => $user,
            'song' => $song
        ]);

        if ($UserSongVoteCounter->getVotesIndc()) {
            $song->setVoteUp($song->getVoteUp() - 1);
            $this->em->persist($song);
        } else {
            $song->setVoteDown($song->getVoteDown() - 1);
            $this->em->persist($song);
        }

        $this->em->remove($UserSongVoteCounter);
        $this->em->flush();
    }
}

