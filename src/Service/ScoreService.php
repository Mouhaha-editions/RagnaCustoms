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
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

class ScoreService
{
    private $em;
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel)
    {
        $this->em = $em;
        $this->kernel = $kernel;
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
     * @param SongDifficulty $difficulty
     * @return Score[]
     */
    public function getScoresTop(?Season $season, SongDifficulty $difficulty)
    {
        return array_slice($this->getScoresFiltered($season, $difficulty), 0, 3);
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

    public function ClawwMethod(Song $song)
    {
        $file = $this->kernel->getProjectDir() . '/public' . $song->getInfoDatFile();
        $infoFile = json_decode(file_get_contents($file));
        foreach ($infoFile->_difficultyBeatmapSets[0]->_difficultyBeatmaps as $diff) {
            $diffFile = json_decode(file_get_contents(str_replace('info.dat', $diff->_beatmapFilename, $file)));
            $rank = $diff->_difficultyRank;
            /** @var SongDifficulty $diffEntity */
            $diffEntity = $song->getSongDifficulties()->filter(function (SongDifficulty $sd) use ($rank) {
                return $sd->getDifficultyRank()->getLevel() == $rank;
            })->first();
            $calc = round($this->calculate($diffFile, $infoFile), 4);
            $diffEntity->setClawDifficulty($calc);
        }
        try {
            $this->em->flush();
        } catch (Exception $e) {
            var_dump("song : " . $infoFile->_songName);
            var_dump("diff : " . $rank);
            var_dump("calc : " . $calc);
            var_dump($e->getMessage());
        }
    }

    private function calculate($diffFile, $infoFile)
    {
        $duration = $infoFile->_songApproximativeDuration;
        $notelist = [];
        foreach ($diffFile->_notes as $note) {
            $notelist[] = $note->_time;
        }
        if ($notelist < 10) return 0;

        $notes_per_second = count($notelist) / $duration;

        # get rid of double notes to analyze distances between runes
        $newNoteList = [];
        for ($i = 1; $i < count($notelist); $i++) {
            if (($notelist[$i] - $notelist[$i - 1]) > 0.000005) {
                $newNoteList[] = $notelist[$i - 1];
            }
        }
        if ($newNoteList < 10) return 0;
        $notes_without_doubles = $newNoteList;
        $distance_between_notes = [];
        for ($i = 1; $i < count($notes_without_doubles); $i++) {
            $distance_between_notes[] = $notes_without_doubles[$i] - $notes_without_doubles[$i - 1];
        }
        $standard_deviation = $this->Stand_Deviation($distance_between_notes);
        return pow($notes_per_second, 1.3) * pow($standard_deviation, 0.3);

    }

    function Stand_Deviation($arr)
    {
        $num_of_elements = count($arr);

        $variance = 0.0;
        if ($num_of_elements == 0) return 0;
        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float)sqrt($variance / $num_of_elements);
    }

    public function calculateDifficulties(string $infoDat)
    {
        $calc = [];
        $infoFile = json_decode(file_get_contents($infoDat));
        foreach ($infoFile->_difficultyBeatmapSets[0]->_difficultyBeatmaps as $diff) {
            $diffFile = json_decode(file_get_contents(str_replace('info.dat', $diff->_beatmapFilename, $infoDat)));
            $calc[] = [
                "rank" => $diff->_difficultyRank,
                "fileName" => $diff->_beatmapFilename,
                "algo" => round($this->calculate($diffFile, $infoFile), 4)
            ];
        }
        return $calc;
    }
}

