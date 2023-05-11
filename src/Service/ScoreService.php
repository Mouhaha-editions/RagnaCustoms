<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\ScoreRepository;

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

    public function getGeneralLeaderboardPosition(UserInterface $user, ?Country $country = null)
    {
        $qb = $this->em->getRepository(RankedScores::class)
            ->createQueryBuilder('r')->leftJoin('r.user', 'u')->where('u.id = :user')->setParameter('user', $user);
        if ($country) {
            $qb->leftJoin('u.country', 'c')->andWhere('c.id = :country')->setParameter('country', $country);
        }
        $qb->orderBy("r.totalPPScore", "Desc");
        $mine = $qb->getQuery()->setFirstResult(0)->setMaxResults(1)->getOneOrNullResult();

        if ($mine == null) {
            return null;
        }
        $qb2 =$this->em->getRepository(RankedScores::class)
            ->createQueryBuilder("s")->select('s.id')->where('s.totalPPScore > :my_score')
            ->andWhere('s.user != :me')->setParameter('my_score', $mine->getTotalPPScore())->setParameter('me', $user)->groupBy('s.user');
        if ($country) {
            $qb2->leftJoin("s.user",'u')->leftJoin('u.country', 'c')->andWhere('c.id = :country')->setParameter('country', $country);
        }

        return count($qb2->getQuery()->getResult()) + 1;
    }

    public function archive(?Score $score, $delete = false)
    {

            $scoreHistory = new ScoreHistory();
            $scoreHistory->setUser($score->getUser());
            $scoreHistory->setSongDifficulty($score->getSongDifficulty());
            $scoreHistory->setScore($score->getScore());
            $scoreHistory->setDateRagnarock($score->getDateRagnarock());
            $scoreHistory->setRawPP($score->getRawPP());
            $scoreHistory->setComboBlue($score->getComboBlue());
            $scoreHistory->setComboYellow($score->getComboYellow());
            $scoreHistory->setHit($score->getHit());
            $scoreHistory->setHitDeltaAverage($score->getHitDeltaAverage());
            $scoreHistory->setHitPercentage($score->getHitPercentage());
            $scoreHistory->setMissed($score->getMissed());
            $scoreHistory->setExtra($score->getExtra());
            $scoreHistory->setPercentageOfPerfects($score->getPercentageOfPerfects());
            $scoreHistory->setSession($score->getSession());
            $scoreHistory->setCountry($score->getCountry());
            $scoreHistory->setUserRagnarock($score->getUserRagnarock());
            $scoreHistory->setPlateform($score->getPlateform());

            $this->em->persist($scoreHistory);
            if ($delete) {
                if ($score->getId() != null) {
                    $this->em->remove($score);
                }
            }
            $this->em->flush();

    }

    public function getTop5Wanadev(SongDifficulty $songDiff, UserInterface $user)
    {
        $scores = $this->em->getRepository(Score::class)->createQueryBuilder("s")->where("s.songDifficulty = :diff ")->setParameter('diff', $songDiff)->orderBy('s.score', "DESC")->setMaxResults(5)->setFirstResult(0)->getQuery()->getResult();
        $results = [];
        foreach ($scores as $k => $score) {
            $results[] = $this->getFormattedRank($score, $k + 1);
        }
        $place = $this->getLeaderboardPosition($user, $songDiff);
        $score = null;
        if ($place > 5) {
            $score = $this->em->getRepository(Score::class)->createQueryBuilder("s")->where("s.songDifficulty = :diff ")->andWhere("s.user = :user ")->setParameter('diff', $songDiff)->setParameter('user', $user)->orderBy('s.score', "DESC")->setMaxResults(1)->setFirstResult(0)->getQuery()->getOneOrNullResult();
            $results[] = $this->getFormattedRank($score, $place);

        }

        return $results;
    }

    public function getFormattedRank(Score $score, int $rank)
    {
        return [
            "platform" => $score->getPlateform(),
            "user" => $score->getUserRagnarock(),
            "score" => $score->getScore(),
            "created_at" => $score->getDateRagnarock(),
            "session" => $score->getSession(),
            "pseudo" => $score->getUser()->getUsername(),
            "country" => $score->getCountry(),
            "stats" => [
                "ComboBlue" => $score->getComboBlue(),
                "ComboYellow" => $score->getComboYellow(),
                "Hit" => $score->getHit(),
                "HitDeltaAverage" => $score->getHitDeltaAverage(),
                "HitPercentage" => $score->getHitPercentage(),
                "Missed" => $score->getMissed(),
                "PercentageOfPerfects" => $score->getPercentageOfPerfects()
            ],
            "rank" => $rank
        ];
    }

    public function getLeaderboardPosition(UserInterface $user, SongDifficulty $songDifficulty, $default = '-')
    {
        $mine = $this->em->getRepository(Score::class)->findOneBy([
            'user' => $user,
            'songDifficulty' => $songDifficulty
        ], ["score" => "Desc"]);
        if ($mine == null) {
            return $default;
        }
        return count($this->em->getRepository(Score::class)->createQueryBuilder("s")->select('s.id')->where('s.score > :my_score')->andWhere('s.songDifficulty = :difficulty')->andWhere('s.user != :me')->setParameter('my_score', $mine->getScore())->setParameter('difficulty', $songDifficulty)->setParameter('me', $user)->groupBy('s.user')->getQuery()->getResult()) + 1;
    }

    public function getLeaderboardPositionWithOrdinalSuffix(UserInterface $user, SongDifficulty $songDifficulty, $default = '-')
    {
        return $this->getOrdinalSuffix($this->getLeaderboardPosition($user, $songDifficulty, $default));
    }

    function getOrdinalSuffix($number)
    {
        if(!is_numeric($number)){
            return $number;
        }

        if (($number % 100 >= 11 && $number % 100 <= 13) || $number % 10 === 0) {
            return $number . 'th';
        } elseif ($number % 10 === 1) {
            return $number . 'st';
        } elseif ($number % 10 === 2) {
            return $number . 'nd';
        } elseif ($number % 10 === 3) {
            return $number . 'rd';
        }
        return $number . 'th';
    }

    public function getTheoricalRank(SongDifficulty $songDifficulty, ?float $getScore)
    {
        return count($this->em->getRepository(Score::class)->createQueryBuilder("s")->select('s.id')->where('s.score > :my_score')->andWhere('s.songDifficulty = :difficulty')->setParameter('my_score', $getScore)->setParameter('difficulty', $songDifficulty)->groupBy('s.user')->getQuery()->getResult()) + 1;
    }

    public function calculateTotalPondPPScore(ScoreRepository $scoreRepository, Utilisateur $user)
    {
        $totalPP = 0;
        $scores = $scoreRepository->createQueryBuilder('score')->leftJoin('score.songDifficulty', 'diff')->where('score.user = :user')->andWhere('diff.isRanked = true')->setParameter('user', $user)->addOrderBy('score.rawPP', 'desc')->getQuery()->getResult();

        $index = 0;
        foreach ($scores as $score) {
            $rawPPScore = $score->getRawPP();
            $pondPPScore = $rawPPScore * pow(0.965, $index);
            $totalPP = $totalPP + $pondPPScore;

            //register the weighted PP score
            $score->setWeightedPP(round($pondPPScore, 2));          

            $index++;
        }
        return round($totalPP, 2);
    }

    public function calculateRawPP(Score $score)
    {
        $userScore = $score->getScore() / 100;
        $songLevel = $score->getSongDifficulty()->getDifficultyRank()->getLevel();
        $maxSongScore = $score->getSongDifficulty()->getTheoricalMaxScore();
        // raw pp is calculated by making the ratio between the current score and the theoretical maximum score.
        // it is ponderated by the song level
        $rawPP = (($userScore / $maxSongScore) * (0.4 + 0.1 * $songLevel)) * 100;

        return round($rawPP, 2);
    }
}

