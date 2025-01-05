<?php

namespace App\Service;

use App\Controller\WanadevApiController;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreRepository;
use App\Service\SongService;
use DateTime;

class RankingScoreService
{
    private static array $fakeStats = [];

    public function __construct(
        private readonly ScoreRepository $scoreRepository,
        private readonly RankedScoresRepository $rankedScoresRepository,
        private readonly SongService $songService
    ) {
    }

    public function calculateForSong(Song $song): void
    {
        foreach ($song->getSongDifficulties() as $difficulty) {
            /** @var Score $score */
            foreach ($difficulty->getScores() as $score) {
                if (!$difficulty->isRanked()) {
                    break;
                }

                $rawPP = $this->calculateRawPP($score);
                $score->setRawPP($rawPP);
                $this->scoreRepository->add($score);
                $this->calculateTotalPondPPScore($score->getUser(), $score->isVR(), $score->isOKODO());
            }
        }
    }

    public function calculateRawPP(Score $score): float
    {
        // Raw PP is calculated using the player accuracy, alongside the curve fitted to the specific ranked map based on estimated average and top player accuracies.
        // The curve is linear until getting to average accuracy (which gives 100 PP), 
        // after which it transitions into a exponential growth that hits the level of 500 PP at 95th accuracy percentile (assuming truncated normal distribution with standard deviation 10% accuracy).
        // Since accuracy can also be viewed as a linear scale between minimum and maximum theoretical in-game distances (assuming player played through the whole song), 
        // the distance should be used for players instead of accuracy on any graphs/explanations.
        $accuracy = $this->calculateAccuracyPercentage($score);
        $meanAccuracy = $score->getSongDifficulty()->getEstAvgAccuracy();
        $maxAccuracy = 100;
        $avgPP = 100;
        $maxPP = $score->getSongDifficulty()->getPPCurveMax();

        if ($accuracy <= $meanAccuracy) {
            $rawPP = $avgPP * $accuracy / $meanAccuracy;
        } else {
            $rawPP = $avgPP * pow($maxPP / $avgPP, ($accuracy - $meanAccuracy) / ($maxAccuracy - $meanAccuracy));
        }
        return round($rawPP, 2);
    }

    public function calculateTotalPondPPScore(Utilisateur $user, bool $isVr = true, bool $isOkod = false): bool
    {
        $totalPP = 0;
        $index = 0;
        $qb = $this->scoreRepository
            ->createQueryBuilder('score')
            ->leftJoin('score.songDifficulty', 'diff')
            ->where('score.user = :user')
            ->andWhere('diff.isRanked = true')
            ->setParameter('user', $user)
            ->addOrderBy('score.rawPP', 'desc')
            ->andWhere('score.plateform IS NOT NULL');

        if ($isVr) {
            $qb->andWhere('score.plateform IN (:plateformVr)')
                ->setParameter('plateformVr', WanadevApiController::VR_PLATEFORM);
        } else {
            if ($isOkod) {
                $qb->andWhere('score.plateform IN (:plateformVr)')
                    ->setParameter('plateformVr', WanadevApiController::OKOD_PLATEFORM);
            } else {
                $qb->andWhere('score.plateform IN (:plateformVr)')
                    ->setParameter('plateformVr', WanadevApiController::FLAT_PLATEFORM);
            }
        }

        $scores = $qb->getQuery()->getResult();

        /**
         * @var Score $score
         */
        foreach ($scores as $k => $score) {
            $rawPPScore = $this->calculateRawPP($score);
            $pondPPScore = $rawPPScore * pow(0.965, $index);
            $totalPP = $totalPP + $pondPPScore;
            $score->setRawPP($rawPPScore);
            $score->setWeightedPP(round($pondPPScore, 2));
            $this->scoreRepository->add($score);
            unset($scores[$k]);
            $index++;
        }


        $totalPondPPScore = round($totalPP, 2);
        $this->saveRankedScore($user, $totalPondPPScore, $isVr, $isOkod);

        return true;
    }

    private function saveRankedScore(Utilisateur $user, float $totalPondPPScore, bool $isVr, bool $isOkod): void
    {
        $rankedScore = $this->rankedScoresRepository->findOneBy([
            'user' => $user,
            'plateform' => $isVr ? 'vr' : ($isOkod ? 'flat_okod' : 'flat'),
        ]);

        if ($totalPondPPScore == 0) {
            if ($rankedScore) {
                $this->rankedScoresRepository->remove($rankedScore);
            }

            return;
        }

        if ($rankedScore == null) {
            $rankedScore = new RankedScores();
            $rankedScore->setUser($user);
            $rankedScore->setPlateform($isVr ? 'vr' : ($isOkod ? 'flat_okod' : 'flat'));
        }

        $rankedScore->setTotalPPScore($totalPondPPScore);
        $this->rankedScoresRepository->add($rankedScore);
        unset($rankedScore);
    }

    public function countRanked(Utilisateur $user, bool $isVr = true, bool $isOkod = false)
    {
        $qb = $this->scoreRepository->createQueryBuilder("s")
            ->select('COUNT(s) as count')
            ->where('s.user = :user')
            ->andWhere('s.rawPP IS NOT NULL')
            ->andWhere('s.rawPP != 0')
            ->setParameter('user', $user)
            ->groupBy('s.user');

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

        $res = $qb->getQuery()->getArrayResult();

        return $res ? $res[0]['count'] : 0;
    }

    public function timeAgoShort(Utilisateur $user): string
    {
        /** @var Score $res */
        $res = $this->scoreRepository->createQueryBuilder("s")
            ->select('s')
            ->where('s.user = :user')
            ->andWhere('s.rawPP IS NOT NULL')
            ->andWhere('s.rawPP != 0')
            ->setParameter('user', $user)
            ->orderBy("s.updatedAt", 'Desc')
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return StatisticService::dateDisplayedShort($res->getUpdatedAt());
    }

    public function imagine(SongDifficulty $songDifficulty, Utilisateur $user, $isVR = false): Score
    {
        if (empty(self::$fakeStats[$songDifficulty->getDifficultyRank()->getId()])) {
            $perfect = 100;
            [$comboYellow, $comboBlue] = $this->songService->calculateMaxCombos($songDifficulty);

            $scores = $this->scoreRepository->createQueryBuilder('s')
                ->leftJoin('s.songDifficulty', 'sd')
                ->andWhere('sd.difficultyRank = :sameRank')
                ->setParameter('sameRank', $songDifficulty->getDifficultyRank())
                ->orderBy('s.updatedAt', 'desc')
                ->setFirstResult(0)->setMaxResults(10)
                ->getQuery()->getResult();

            if ($scores) {
                $yellows = [];
                $blues = [];
                $perfects = [];
                /** @var Score $tmpScore */
                foreach ($scores as $tmpScore) {
                    $yellows[] = $tmpScore->getComboYellow();
                    $blues[] = $tmpScore->getComboBlue();
                    $perfects[] = $tmpScore->getPercentageOfPerfects();
                }

                $perfect = floor(array_sum($perfects) / count($perfects));
                $comboBlue = floor(array_sum($blues) / count($blues));
                $comboYellow = floor(array_sum($yellows) / count($yellows));
                self::$fakeStats[$songDifficulty->getDifficultyRank()->getId()] = [
                    'b' => $comboBlue,
                    'y' => $comboYellow,
                    'p' => $perfect,
                ];
            }
        } else {
            $comboBlue = self::$fakeStats[$songDifficulty->getDifficultyRank()->getId()]['b'];
            $comboYellow = self::$fakeStats[$songDifficulty->getDifficultyRank()->getId()]['y'];
            $perfect = self::$fakeStats[$songDifficulty->getDifficultyRank()->getId()]['p'];
        }

        $score = new Score();
        $score->setUser($user);
        $score->setSongDifficulty($songDifficulty);
        $score->setPercentageOfPerfects($perfect);
        $score->setComboBlue($comboBlue);
        $score->setComboYellow($comboYellow);
        $score->setHitPercentage(100);
        $score->setRawPP($this->calculateRawPP($score));
        $score->setCreatedAt(new DateTime());
        $score->setUpdatedAt(new DateTime());
        $score->setDateRagnarock(new DateTime());

        return $score;
    }

    private function calculateAccuracyPercentage(Score $score): float
    {
        // Only use distance - this can give lower values than Accuracy formula if player started map late (game counts distance from the first hit note),
        // but in this case the player should just replay the full map to get better score.
        $minDistance = $score->getSongDifficulty()->getTheoricalMinScore();
        $maxDistance = $score->getSongDifficulty()->getTheoricalMaxScore();
        // Clamp distance to the range for accuracy
        $distance = max($minDistance, min($maxDistance, $score->getScore() / 100.0));

        return 100 * ($distance - $minDistance) / ($maxDistance - $minDistance);
    }

}
