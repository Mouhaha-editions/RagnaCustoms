<?php

namespace App\Service;

use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Season;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\ScoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
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
        $qb->orderBy("s.score", 'DESC');
        $score = $qb->setFirstResult(0)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if ($score == null) {
            return null;
        }
        $return['score'] = $score;

        $qb = $this->em->getRepository(Score::class)->createQueryBuilder("s")
            ->select("MAX(s.score)")
            ->where('s.difficulty = :difficulty')
            ->andWhere("s.hash = :hash")
            ->having("MAX(s.score) >= :score")
            ->andWhere("s.user != :user")
            ->setParameter('score', $score->getScore())
            ->setParameter('user', $user)
            ->setParameter('hash', $hash)
            ->setParameter('difficulty', $level);
        if ($season) {
            $qb->andWhere('s.season = :season')
                ->setParameter('season', $season);
        }
        $qb->groupBy('s.user');

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
        return array_filter($scores, function (Score $score) use (&$set) {
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
        return array_slice($this->getScoresFiltered($season, $difficulty), 0, 3);
    }

    /**
     * @param Utilisateur $user
     * @param int $maxresult
     * @return ScoreHistory[]|ArrayCollection
     */
    public function LastFromUser(Utilisateur $user, $maxresult = 20)
    {
        return $this->em->getRepository(ScoreHistory::class)->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.updatedAt', "desc")
            ->setFirstResult(0)
            ->setMaxResults($maxresult)
            ->getQuery()->getResult();
    }

    public function getRanking(Utilisateur $user, $type)
    {
        switch ($type) {
            case 'global':
                $conn = $this->em->getConnection();

                $sql = '
           SELECT SUM(max_score)/1000 AS score, 
                  username,
                  user_id,
                  MD5(LOWER(email)) as gravatar, 
                  COUNT(*) AS count_song 
           FROM (
                SELECT u.username,
                       u.email,
                       s.user_id, 
                       MAX(s.score) AS max_score 
                FROM score s 
                    LEFT JOIN song sg ON sg.new_guid = s.hash 
                    LEFT JOIN utilisateur u on s.user_id = u.id        
                WHERE sg.id IS NOT null AND sg.wip != true
                GROUP BY s.hash,s.difficulty,s.user_id
            ) AS ms GROUP BY user_id ORDER BY score DESC';
                $stmt = $conn->prepare($sql);
                $result = $stmt->executeQuery();
                $scores = $result->fetchAllAssociative();
                $i = 1;
                foreach ($scores as $score) {
                    if ($score['user_id'] == $user->getId()) {
                        return $i;
                    }
                    $i++;
                }
                return 'unknown';
                break;
            case 'season':
                $season = $this->em->getRepository(Season::class)->getCurrent();
                $conn = $this->em->getConnection();
                $sql = '
           SELECT SUM(max_score)/1000 AS score, 
                  username,
                  user_id,
                  MD5(LOWER(email)) as gravatar, 
                  COUNT(*) AS count_song 
           FROM (
                SELECT u.username,
                       u.email,
                       s.user_id, 
                       MAX(s.score) AS max_score 
                FROM score s 
                    LEFT JOIN song sg ON sg.new_guid = s.hash 
                    LEFT JOIN utilisateur u on s.user_id = u.id        
                WHERE sg.id IS NOT null AND s.season_id = :season AND sg.wip != true
                GROUP BY s.hash,s.difficulty,s.user_id
            ) AS ms GROUP BY user_id ORDER BY score DESC';
                $stmt = $conn->prepare($sql);
                $result = $stmt->executeQuery(['season' => $season->getId()]);
                $scores = $result->fetchAllAssociative();

                $i = 1;
                foreach ($scores as $score) {
                    if ($score['user_id'] == $user->getId()) {
                        return $i;
                    }
                    $i++;
                }
                return 'unknown';

                break;
        }

    }
}

