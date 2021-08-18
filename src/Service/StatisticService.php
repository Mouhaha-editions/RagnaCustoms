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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\VarDumper\VarDumper;

class StatisticService
{
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
        while ($first < new DateTime()) {
            $days[] = $first->format('Y-m-d');
            $first->modify("+1 day");
        }
        return $days;
    }

    public function getViewsLastXDays($days, Song $song)
    {
        $first = (new DateTime())->modify("-" . $days . " days");
        $data = [];
        while ($first < new DateTime()) {
            $result = $this->em->getRepository(ViewCounter::class)->createQueryBuilder("d")
                ->select('COUNT(d) AS nb')
                ->where("d.song = :song")
                ->andWhere("d.createdAt LIKE :date")
                ->setParameter('song', $song)
                ->setParameter('date', $first->format('Y-m-d')."%")
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            $first->modify("+1 day");
            $data[] = $result['nb'];
        }
        return $data;
    }

    public function getDownloadsLastXDays($days, Song $song)
    {
        $first = (new DateTime())->modify("-" . $days . " days");
        $data = [];
        while ($first < new DateTime()) {
            $result = $this->em->getRepository(DownloadCounter::class)->createQueryBuilder("d")
                ->select('COUNT(d) AS nb')
                ->where("d.song = :song")
                ->andWhere("d.createdAt LIKE :date")
                ->setParameter('song', $song)
                ->setParameter('date', $first->format('Y-m-d')."%")
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            $first->modify("+1 day");

            $data[] = $result['nb'];
        }
        return $data;
    }


    public function getPlayedLastXDays($days, Song $song)
    {
        VarDumper:dump("#1");

        $hashes = $song->getHashes();

        $first = (new DateTime())->modify("-" . $days . " days");
        $data = [];
        while ($first < new DateTime()) {

            $result = $this->em->getRepository(ScoreHistory::class)->createQueryBuilder("d")
                ->select('COUNT(d) AS nb')
                ->where("d.hash IN (:hashes)")
                ->andWhere("d.createdAt LIKE :date")
                ->setParameter('hashes', $hashes)
                ->setParameter('date', $first->format('Y-m-d')."%")
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            $first->modify("+1 day");
            $data[] = $result['nb'];
        }
        return $data;
    }
}

