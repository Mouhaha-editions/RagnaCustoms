<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\Vote;

class VoteService
{
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
}

