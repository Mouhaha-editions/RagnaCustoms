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
use DateTime;

class RankingScoreService
{
    private static array $fakeStats = [];

    public function __construct(
        private readonly ScoreRepository $scoreRepository,
        private readonly RankedScoresRepository $rankedScoresRepository,
    ) {
    }

    public function calculateForSong(Song $song)
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

    public function calculateRawPP(Score $score)
    {
        $duration = $score->getSongDifficulty()->getSong()->getApproximativeDuration();
        $perfects = $score->getPercentageOfPerfects();
        $notesCount = $score->getSongDifficulty()->getNotesCount();
        $YellowCombos = $score->getComboYellow();
        $blueCombos = $score->getComboBlue();
        $combos = 2 * $YellowCombos + $blueCombos;
        $songLevel = $score->getSongDifficulty()->getDifficultyRank()->getLevel();
        // raw pp is calculated by making the ratio between the current score and the theoretical maximum score.
        // it is ponderated by the song level

        $scoreSongLevel = $songLevel ** ($perfects / 170);
        $scoreNoteCount = $notesCount ** ($perfects / 150);
        $scoreCombos = ($combos * $duration) ** ($perfects / 150);

        $rawPP = ((($scoreSongLevel * $scoreNoteCount) + $scoreCombos) / $duration) * 300;

        $score->setRawPP($rawPP);

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

    private function saveRankedScore(Utilisateur $user, float $totalPondPPScore, bool $isVr, bool $isOkodo): void
    {
        if ($totalPondPPScore == 0) {
            return;
        }

        $rankedScore = $this->rankedScoresRepository->findOneBy([
            'user' => $user,
            'plateform' => $isVr ? 'vr' : ($isOkodo ? 'flat_okodo' : 'flat'),
        ]);

        if ($rankedScore == null) {
            $rankedScore = new RankedScores();
            $rankedScore->setUser($user);
            $rankedScore->setPlateform($isVr ? 'vr' : ($isOkodo ? 'flat_okodo' : 'flat'));
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

    public function timeAgoShort(Utilisateur $user)
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
            $comboBlue = 0;
            $comboYellow = $this->yellowComboMax($songDifficulty);

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

    public function yellowComboMax(SongDifficulty $diff): int
    {
        // we consider that no note were missed
        $miss = 0;
        // We consider that none blue combo is used
        $maxBlueCombo = 0;
        // base speed of the boat given by Wanadev
        $baseSpeed = 17.18;
        $duration = $diff->getSong()->getApproximativeDuration();
        $noteCount = $diff->getNotesCount();

        //calculation of the theorical number of yellow combos
        $consumedNotes = 0;
        $combo = 0;
        $maxYellowCombo = 0;
        while ($consumedNotes <= $noteCount) {
            $combo = $combo + 1;
            $consumedNotes = $consumedNotes + (2 * (15 + 10 * $combo));

            $maxYellowCombo = $combo - 1;
        }

        return $maxYellowCombo;
    }

}
