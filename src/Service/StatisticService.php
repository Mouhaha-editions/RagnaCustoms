<?php

namespace App\Service;

use App\Entity\DownloadCounter;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Entity\ViewCounter;
use App\Entity\Vote;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\VarDumper\VarDumper;

class StatisticService
{
    /** @var array */
    private static $StatisticsOnScoreHistory;

    private $security;
    protected $requestStack;
    protected $em;

    public function __construct(Security $security, RequestStack $requestStack, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function getLastXDays($days = 30)
    {
        $first = (new DateTime())->modify("-" . $days . " days");
        $days = [];
        while ($first <= new DateTime()) {
            $days[] = $first->format('Y-m-d');
            $first->modify("+1 day");
        }
        return $days;
    }

    /**
     * @param $days
     * @param Song $song
     * @return array
     * @throws NonUniqueResultException
     */
    public function getViewsLastXDays($days, Song $song)
    {
        $first = (new DateTime())->modify("-" . $days . " days");
        $data = [];
        $i = 0;
        while ($first <= new DateTime()) {
            $result = $this->em->getRepository(ViewCounter::class)->createQueryBuilder("d")
                ->select('COUNT(d) AS nb')
                ->where("d.song = :song")
                ->andWhere("d.createdAt LIKE :date")
                ->setParameter('song', $song)
                ->setParameter('date', $first->format('Y-m-d') . "%")
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            $first->modify("+1 day");
            if ($i == 0) {
                $data[] = $result['nb'];
            } else {
                $data[] = $data[$i - 1] + $result['nb'];
            }
            $i++;
        }
        return $data;
    }

    public function getDownloadsLastXDays($days, Song $song)
    {
        $first = (new DateTime())->modify("-" . $days . " days");
        $data = [];
        $i = 0;
        while ($first <= new DateTime()) {
            $result = $this->em->getRepository(DownloadCounter::class)->createQueryBuilder("d")
                ->select('COUNT(d) AS nb')
                ->where("d.song = :song")
                ->andWhere("d.createdAt LIKE :date")
                ->setParameter('song', $song)
                ->setParameter('date', $first->format('Y-m-d') . "%")
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            $first->modify("+1 day");

            if ($i == 0) {
                $data[] = $result['nb'];
            } else {
                $data[] = $data[$i - 1] + $result['nb'];
            }
            $i++;
        }
        return $data;
    }


    public function getPlayedLastXDays($days, Song $song)
    {

        $hashes = $song->getHashes();

        $first = (new DateTime())->modify("-" . $days . " days");
        $data = [];
        $i = 0;
        while ($first <= new DateTime()) {
            $result = $this->em->getRepository(ScoreHistory::class)->createQueryBuilder("d")
                ->select('COUNT(d) AS nb')
                ->where("d.hash IN (:hashes)")
                ->andWhere("d.createdAt LIKE :date")
                ->setParameter('hashes', $hashes)
                ->setParameter('date', $first->format('Y-m-d') . "%")
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            $first->modify("+1 day");
            if ($i == 0) {
                $data[] = $result['nb'];
            } else {
                $data[] = $data[$i - 1] + $result['nb'];
            }
            $i++;

        }
        return $data;
    }

    public function getStatisticsScoreHistory(Utilisateur $user)
    {
        if (self::$StatisticsOnScoreHistory == null) {
            $result = $this->em->getRepository(ScoreHistory::class)->createQueryBuilder("d")
                ->select('SUM(d.score) AS distance')
                ->addSelect('SUM(d.noteHit) AS count_notes_hit')
                ->addSelect('SUM(d.noteMissed) AS count_notes_missed')
                ->addSelect('SUM(d.noteNotProcessed) AS count_notes_not_processed')
                ->where("d.user = :user")
                ->setParameter('user', $user)
                ->groupBy('d.user')
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            self::$StatisticsOnScoreHistory = $result ?? [
                    "distance" => 0,
                    "count_notes_hit" => 0,
                    "count_notes_missed" => 0,
                    "count_notes_not_processed" => 0,
                ];
        }
    }

    public function getTotalDistance(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['distance'];
    }

    public function getTotalNotesMissed(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['count_notes_missed'];
    }

    public function getTotalNotesHit(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['count_notes_hit'];
    }

    public function getTotalNotesNotProcessed(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['count_notes_not_processed'];
    }
}

