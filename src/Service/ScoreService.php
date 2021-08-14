<?php

namespace App\Service;

use App\Entity\Score;
use App\Entity\Season;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

class ScoreService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getMine(Utilisateur $user, SongDifficulty $songDifficulty, ?Season $season)
    {
        $return = [];
        $level = $songDifficulty->getDifficultyRank()->getLevel();
        $hash = $songDifficulty->getSong()->getNewGuid();

        /** @var Score $score */
        $qb = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->where('s.user = :user')
            ->andWhere('s.difficulty = :difficulty')
            ->andWhere('s.hash = :hash')
            ->setParameter('user', $user)
            ->setParameter('hash', $hash)
            ->setParameter('difficulty', $level);
        if ($season) {
            $qb->andWhere('s.season = :season')
                ->setParameter('season', $season);
        }

        $score = $qb->setFirstResult(0)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if ($score == null) {
            return null;
        }
        $return['score'] = $score;

        $qb = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->where('s.difficulty = :difficulty')
            ->andWhere("s.hash = :hash")
            ->andWhere("s.score >= :score")
            ->andWhere("s.user != :user")
            ->setParameter('score', $score->getScore())
            ->setParameter('user', $user)
            ->setParameter('hash', $hash)
            ->setParameter('difficulty', $level);
        if ($season) {
            $qb->andWhere('s.season = :season')
                ->setParameter('season', $season);
        }
        $otherScore = $qb->getQuery()->getResult();
        $return['place'] = count($otherScore) + 1;
        return $return;
    }

    /**
     * @param Season|null $season
     * @param SongDifficulty $songDifficulty
     * @return mixed
     */
    public function getScoresFiltered(?Season $season, SongDifficulty $songDifficulty)
    {
        $set = [];
        $scores = $this->em->getRepository(Score::class)->findBySeasonDiffHash($season, $songDifficulty->getDifficultyRank()->getLevel(), $songDifficulty->getSong()->getNewGuid());
        return array_filter($scores,function (Score $score) use (&$set) {
            if (in_array($score->getUser()->getId(), $set)) {
                return false;
            }
            $set[] = $score->getUser()->getId();
            return true;
        });
    }

    /**
     * @param Season|null $season
     * @param SongDifficulty $difficulty
     * @return Score[]
     */
    public function getScoresTop(?Season $season, SongDifficulty $difficulty)
    {
        return array_slice($this->getScoresFiltered($season, $difficulty),0, 3);
    }

}

