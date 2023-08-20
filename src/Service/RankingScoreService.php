<?php

namespace App\Service;

use App\Controller\WanadevApiController;
use App\Entity\RankedScores;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Repository\RankedScoresRepository;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;

class RankingScoreService
{
    public function __construct(
        private ScoreRepository $scoreRepository,
        private EntityManagerInterface $entityManager,
        private RankedScoresRepository $rankedScoresRepository,
    ) {
    }

    public function calculateForSong(Song $song)
    {
        foreach ($song->getSongDifficulties() as $difficulty) {
            foreach ($difficulty->getScores() as $score) {
                if (!$difficulty->isRanked()) {
                    break;
                }

                $rawPP = $this->calculateRawPP($score);
                $score->setRawPP($rawPP);
                $this->scoreRepository->add($score);
                $this->calculateTotalPondPPScore($score->getUser(), $score->isVR());
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

    public function calculateTotalPondPPScore(Utilisateur $user, bool $isVr = true): bool
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
            $qb->andWhere('score.plateform IN (:plateform) ')
                ->setParameter('plateform', WanadevApiController::VR_PLATEFORM);
        } else {
            $qb->andWhere('score.plateform NOT IN (:plateform) ')
                ->setParameter('plateform', WanadevApiController::VR_PLATEFORM);
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
        $this->saveRankedScore($user, $totalPondPPScore, $isVr);

        return true;
    }

    private function saveRankedScore(Utilisateur $user, float $totalPondPPScore, bool $isVr): void
    {
        $rankedScore = $this->rankedScoresRepository->findOneBy([
            'user' => $user,
            'plateform' => $isVr ? 'vr' : 'flat'
        ]);

        if ($rankedScore == null) {
            $rankedScore = new RankedScores();
            $rankedScore->setUser($user);
            $rankedScore->setPlateform($isVr ? 'vr' : 'flat');
        }

        $rankedScore->setTotalPPScore($totalPondPPScore);
        $this->rankedScoresRepository->add($rankedScore);
        unset($rankedScore);
    }

    public function countRanked(Utilisateur $user, bool $isVr = true)
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
            $qb->andWhere('s.plateform NOT IN (:vr)')
                ->setParameter('vr', WanadevApiController::VR_PLATEFORM);
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
        return StatisticService::dateDiplayerShort($res->getUpdatedAt());
    }
}