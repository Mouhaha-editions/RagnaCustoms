<?php

namespace App\Service;

use App\Controller\WanadevApiController;
use App\Entity\Country;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\FriendRepository;
use App\Repository\ScoreHistoryRepository;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ScoreService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ScoreRepository $scoreRepository,
        private readonly ScoreHistoryRepository $scoreHistoryRepository,
        private readonly FriendRepository $friendRepository
    ) {
    }

    public function calculateDifficulties(string $infoDat): array
    {
        $calc = [];
        $infoFile = json_decode(file_get_contents($infoDat));
        foreach ($infoFile->_difficultyBeatmapSets[0]->_difficultyBeatmaps as $diff) {
            $diffFile = json_decode(file_get_contents(str_replace('info.dat', $diff->_beatmapFilename, $infoDat)));
            $calc[] = [
                'rank' => $diff->_difficultyRank,
                'fileName' => $diff->_beatmapFilename,
                'algo' => round($this->calculate($diffFile, $infoFile), 4),
            ];
        }

        return $calc;
    }

    private function calculate($diffFile, $infoFile): float|int
    {
        $duration = $infoFile->_songApproximativeDuration;
        $notelist = [];
        foreach ($diffFile->_notes as $note) {
            $notelist[] = $note->_time;
        }
        if ($notelist < 10) {
            return 0;
        }

        $notes_per_second = count($notelist) / $duration;

        # get rid of double notes to analyze distances between runes
        $newNoteList = [];
        for ($i = 1; $i < count($notelist); $i++) {
            if (($notelist[$i] - $notelist[$i - 1]) > 0.000005) {
                $newNoteList[] = $notelist[$i - 1];
            }
        }
        if ($newNoteList < 10) {
            return 0;
        }
        $notes_without_doubles = $newNoteList;
        $distance_between_notes = [];
        for ($i = 1; $i < count($notes_without_doubles); $i++) {
            $distance_between_notes[] = $notes_without_doubles[$i] - $notes_without_doubles[$i - 1];
        }
        $standard_deviation = $this->standDeviation($distance_between_notes);

        return pow($notes_per_second, 1.3) * pow($standard_deviation, 0.3);
    }

    function standDeviation($arr): float|int
    {
        $num_of_elements = count($arr);

        $variance = 0.0;
        if ($num_of_elements == 0) {
            return 0;
        }
        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float)sqrt($variance / $num_of_elements);
    }

    public function getGeneralLeaderboardPosition(UserInterface $user, ?Country $country = null, bool $isVr = true)
    {
        $qb = $this->em->getRepository(RankedScores::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->where('u.id = :user')
            ->setParameter('user', $user);

        if ($country) {
            $qb->leftJoin('u.country', 'c')->andWhere('c.id = :country')->setParameter('country', $country);
        }

        if ($isVr) {
            $qb->andWhere('r.plateform = \'vr\'');
        } else {
            $qb->andWhere('r.plateform = \'flat\'');
        }

        $qb->orderBy('r.totalPPScore', 'Desc');
        $mine = $qb->getQuery()
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($mine == null) {
            return null;
        }
        $qb2 = $this->em->getRepository(RankedScores::class)
            ->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.totalPPScore > :my_score')
            ->andWhere('s.user != :me')
            ->setParameter('my_score', $mine->getTotalPPScore())
            ->setParameter('me', $user)
            ->groupBy('s.user');
        if ($isVr) {
            $qb2->andWhere('s.plateform = \'vr\'');
        } else {
            $qb2->andWhere('s.plateform = \'flat\'');
        }

        if ($country) {
            $qb2->leftJoin('s.user', 'u')->leftJoin('u.country', 'c')->andWhere('c.id = :country')->setParameter(
                'country',
                $country
            );
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

    public function getScore(SongDifficulty $songDifficulty, Utilisateur $user, $isVR = false)
    {
        return $user->scorePlateform($songDifficulty, $isVR);
    }

    public function getTop5Wanadev(
        SongDifficulty $songDiff,
        UserInterface $user,
        array $returnPlatforms = [],
        bool $isVr = true,
        bool $friendsOnly = false,
        array $friendsRagnarock = []
    ) {
        $qb = $this->em->getRepository(Score::class)
            ->createQueryBuilder('s')
            ->where('s.songDifficulty = :diff')
            ->setParameter('diff', $songDiff)
            ->orderBy('s.score', 'DESC')
            ->setMaxResults(5)
            ->setFirstResult(0);

        if (!empty($returnPlatforms)) {
            $qb->andWhere('s.plateform IN (:vr)')
                ->setParameter('vr', $returnPlatforms);
        }

        if ($friendsOnly) {
            $friends = [$user];
            $friendRequests = $this->friendRepository->getMine($user);

            foreach ($friendRequests as $friendRequest) {
                $friends[] = $friendRequest->getOther($user);
            }

            $qb->andWhere(
                $qb->expr()->orX(
                    's.user IN (:friends)',
                    's.userRagnarock IN (:friends_ragnarock)',
                )
            )
                ->setParameter('friends_ragnarock', $friendsRagnarock)
                ->setParameter('friends', $friends);
        }

        $scores = $qb->getQuery()->getResult();
        $results = [];

        foreach ($scores as $k => $score) {
            $results[] = $this->getFormattedRank($score, $k + 1);
        }

        $vr = false;
        $flat = false;

        foreach ($returnPlatforms as $platform) {
            if (in_array($platform, WanadevApiController::VR_PLATEFORM)) {
                $vr = true;
            } else {
                $flat = true;
            }
        }

        $both = $vr && $flat;

        $place = $this->getLeaderboardPosition($user, $songDiff, '-', $both, $isVr, $friendsOnly, $friendsRagnarock);
        $score = null;

        if ($place > 5) {
            $qb = $this->em->getRepository(Score::class)
                ->createQueryBuilder('s')
                ->where('s.songDifficulty = :diff')
                ->andWhere('s.user = :user')
                ->setParameter('diff', $songDiff)
                ->setParameter('user', $user)
                ->orderBy('s.score', 'DESC')
                ->setMaxResults(1)
                ->setFirstResult(0);

            if (!empty($returnPlatforms)) {
                $qb->andWhere('s.plateform IN (:vr)')
                    ->setParameter('vr', $returnPlatforms);
            }

            if ($friendsOnly) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        's.user IN (:friends)',
                        's.userRagnarock IN (:friends_ragnarock)',
                    )
                )
                    ->setParameter('friends_ragnarock', $friendsRagnarock)
                    ->setParameter('friends', $friends);
            }

            $score = $qb->getQuery()->getOneOrNullResult();
            if ($score != null) {
                $results[] = $this->getFormattedRank($score, $place);
            }
        }

        return $results;
    }

    public function getFormattedRank(Score $score, int $rank)
    {
        return [
            'platform' => $score->getPlateform(),
            'user' => $score->getUserRagnarock(),
            'score' => $score->getScore(),
            'created_at' => $score->getDateRagnarock(),
            'session' => $score->getSession(),
            'pseudo' => $score->getUser()->getUsername(),
            'country' => $score->getCountry(),
            'stats' => [
                'ComboBlue' => $score->getComboBlue(),
                'ComboYellow' => $score->getComboYellow(),
                'Hit' => $score->getHit(),
                'HitDeltaAverage' => $score->getHitDeltaAverage(),
                'HitPercentage' => $score->getHitPercentage(),
                'Missed' => $score->getMissed(),
                'PercentageOfPerfects' => $score->getPercentageOfPerfects(),
            ],
            'rank' => $rank,
        ];
    }

    public function getLeaderboardPosition(
        UserInterface $user,
        SongDifficulty $songDifficulty,
        $default = '-',
        bool $both = true,
        bool $isVr = true,
        bool $friendsOnly = false,
        array $friendsRagnarock = [],
        bool $isOkod = false
    ) {
        $qb = $this->em
            ->getRepository(Score::class)
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.songDifficulty = :songDifficulty')
            ->setParameter('user', $user)
            ->setParameter('songDifficulty', $songDifficulty)
            ->orderBy('s.score', 'DESC');


        if ($isVr) {
            $qb->andWhere('s.plateform IN (:vr)')
                ->setParameter('vr', WanadevApiController::VR_PLATEFORM);
        } else {
            if ($isOkod) {
                $qb->andWhere('s.plateform IN (:plateformVr)')
                    ->setParameter('plateformVr', WanadevApiController::OKOD_PLATEFORM);
            } else {
                $qb->andWhere('s.plateform IN (:plateformVr)')
                    ->setParameter('plateformVr', WanadevApiController::FLAT_PLATEFORM);
            }
        }

        if ($friendsOnly) {
            $friends = [$user];
            $friendRequests = $this->friendRepository->getMine($user);

            foreach ($friendRequests as $friendRequest) {
                $friends[] = $friendRequest->getOther($user);
            }

            $qb->andWhere(
                $qb->expr()->orX(
                    's.user IN (:friends)',
                    's.userRagnarock IN (:friends_ragnarock)',
                )
            )
                ->setParameter('friends_ragnarock', $friendsRagnarock)
                ->setParameter('friends', $friends);
        }

        $mine = $qb->getQuery()->getOneOrNullResult();

        if ($mine == null) {
            return $default;
        }

        $qb = $this->em->getRepository(Score::class)
            ->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.score > :my_score')
            ->andWhere('s.songDifficulty = :difficulty')
            ->andWhere('s.user != :me')
            ->setParameter('my_score', $mine->getScore())
            ->setParameter('difficulty', $songDifficulty)
            ->setParameter('me', $user)
            ->groupBy('s.user');

        if (!$both) {
            if ($isVr) {
                $qb->andWhere('s.plateform IN (:vr)')
                    ->setParameter('vr', WanadevApiController::VR_PLATEFORM);
            } else {
                $qb->andWhere('s.plateform NOT IN (:vr)')
                    ->setParameter('vr', WanadevApiController::VR_PLATEFORM);
            }
        }

        if ($friendsOnly) {
            $qb->andWhere(
                $qb->expr()->orX(
                    's.user IN (:friends)',
                    's.userRagnarock IN (:friends_ragnarock)',
                )
            )
                ->setParameter('friends_ragnarock', $friendsRagnarock)
                ->setParameter('friends', $friends);
        }

        return count($qb->getQuery()->getResult()) + 1;
    }

    public function getLeaderboardPositionWithOrdinalSuffix(
        UserInterface $user,
        SongDifficulty $songDifficulty,
        $default = '-',
        bool $isVr = true,
        bool $isOkod = false

    ) {
        return $this->getOrdinalSuffix(
            $this->getLeaderboardPosition($user, $songDifficulty, $default, false, $isVr, false, [], $isOkod)
        );
    }

    function getOrdinalSuffix($number)
    {
        if (!is_numeric($number)) {
            return $number;
        }

        if (($number % 100 >= 11 && $number % 100 <= 13) || $number % 10 === 0) {
            return $number.'th';
        } elseif ($number % 10 === 1) {
            return $number.'st';
        } elseif ($number % 10 === 2) {
            return $number.'nd';
        } elseif ($number % 10 === 3) {
            return $number.'rd';
        }

        return $number.'th';
    }

    public function getTheoricalRank(SongDifficulty $songDifficulty, ?float $getScore, array $plateforms = [])
    {
        $qb = $this->em->getRepository(Score::class)
            ->createQueryBuilder('s')
            ->select('s.id')
            ->where('s.score > :my_score')
            ->andWhere('s.songDifficulty = :difficulty')
            ->setParameter('my_score', $getScore)
            ->setParameter('difficulty', $songDifficulty)
            ->groupBy('s.user');

        if (!empty($plateforms)) {
            $qb->andWhere('s.plateform IN (:vr)')
                ->setParameter('vr', $plateforms);
        }

        return count($qb->getQuery()->getResult()) + 1;
    }

    public function updateSessions(Utilisateur $user, SongDifficulty $songDiff, bool $isVR, string $session)
    {
        $qb = $this->scoreHistoryRepository
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.songDifficulty = :songDifficulty')
            ->setParameter('songDifficulty', $songDiff)
            ->setParameter('plateformVr', WanadevApiController::VR_PLATEFORM);

        if ($isVR) {
            $qb->andWhere('s.plateform IN (:plateformVr)');
        } else {
            $qb->andWhere('s.plateform NOT IN (:plateformVr)');
        }

        $histories = $qb->getQuery()->getResult();

        /** @var ScoreHistory $history */
        foreach ($histories as $history) {
            $history->setSession($session);
            $this->scoreHistoryRepository->add($history);
        }

        $qb = $this->scoreRepository
            ->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.songDifficulty = :songDifficulty')
            ->setParameter('songDifficulty', $songDiff)
            ->setParameter('plateformVr', WanadevApiController::VR_PLATEFORM);

        if ($isVR) {
            $qb->andWhere('s.plateform IN (:plateformVr)');
        } else {
            $qb->andWhere('s.plateform NOT IN (:plateformVr)');
        }

        $scores = $qb->getQuery()->getResult();

        foreach ($scores as $score) {
            $score->setSession($session);
            $this->scoreRepository->add($score);
        }
    }
}

