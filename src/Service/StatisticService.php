<?php

namespace App\Service;

use App\Entity\DownloadCounter;
use App\Entity\Score;
use App\Entity\ScoreHistory;
use App\Entity\Song;
use App\Entity\Utilisateur;
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
        if (self::$StatisticsOnScoreHistory == null || count(self::$StatisticsOnScoreHistory) == 0 ) {
            $result = $this->em->getRepository(ScoreHistory::class)->createQueryBuilder("d")
                ->select('SUM(d.score) AS distance')
                ->addSelect('SUM(d.hit) AS count_notes_hit')
                ->addSelect('SUM(d.missed) AS count_notes_missed')
                ->addSelect('0 AS count_notes_not_processed')
                ->where("d.user = :user")
                ->setParameter('user', $user)
                ->groupBy('d.user')
                ->setFirstResult(0)->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
            if($result != null) {
                foreach ($result as $k => $v) {
                    self::$StatisticsOnScoreHistory[$k] = $v ?? 0;
                }
            }
        }
    }

    public function getTotalDistance(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['distance']??0;
    }

    public function getTotalNotesMissed(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['count_notes_missed']??0;
    }

    public function getTotalNotesHit(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['count_notes_hit']??0;
    }

    public function getTotalNotesNotProcessed(Utilisateur $user)
    {
        $this->getStatisticsScoreHistory($user);
        return self::$StatisticsOnScoreHistory['count_notes_not_processed']??0;
    }

    public static function dateDiplayer(\DateTimeInterface $date){

        $difference = $date->diff(new DateTime(), true);

        $etime = date_create('@0')->add($difference)->getTimestamp();

        if ($etime < 1)
        {
            return '0 seconds';
        }

        $a = array( 365 * 24 * 60 * 60  =>  'year',
            30 * 24 * 60 * 60  =>  'month',
            24 * 60 * 60  =>  'day',
            60 * 60  =>  'hour',
            60  =>  'minute',
            1  =>  'second'
        );
        $a_plural = array( 'year'   => 'years',
            'month'  => 'months',
            'day'    => 'days',
            'hour'   => 'hours',
            'minute' => 'minutes',
            'second' => 'seconds'
        );

        foreach ($a as $secs => $str)
        {
            $d = $etime / $secs;
            if ($d >= 1)
            {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
            }
        }
        return '99999 seconds';

    }

    public static function dateDiplayerShort(\DateTimeInterface $date){

            $difference = $date->diff(new DateTime(), true);

            $etime = date_create('@0')->add($difference)->getTimestamp();

            if ($etime < 1)
            {
                return '0 seconds';
            }

            $a = array( 365 * 24 * 60 * 60  =>  'y',
                30 * 24 * 60 * 60  =>  'mo',
                24 * 60 * 60  =>  'd',
                60 * 60  =>  'h',
                60  =>  'm',
                1  =>  's'
            );

            foreach ($a as $secs => $str)
            {
                $d = $etime / $secs;
                if ($d >= 1)
                {
                    $r = round($d);
                    return $r . $str . ' ago';
                }
            }
            return '99999 seconds';

        }

        /** idealement travailler avec une interface plutot que deux faire deux methodes */
    public function getScatterDataSetsByScore(?Score $sh)
    {
        $raw_data = json_decode(json_decode(($sh->getExtra())))->HitDeltaTimes;
        $song_file = "../public/".$sh->getSongDifficulty()->getDifficultyFile();
       return $this->getScatterDatasets($raw_data, $song_file);
    }

    public function getScatterDataSetsByScorehistory(?ScoreHistory $sh)
    {
        $raw_data = json_decode(json_decode(($sh->getExtra())))->HitDeltaTimes;
        $song_file = "../public/".$sh->getSongDifficulty()->getDifficultyFile();
        return $this->getScatterDatasets($raw_data, $song_file);
    }

    private function getScatterDatasets($raw_data, string $song_file)
    {
        $json = json_decode(file_get_contents($song_file));
        $notes = $json->_notes;
        for ($i = 0; $i < count($raw_data); $i++) {
            if ($raw_data[$i] == -1000) {
                $raw_data[$i] = -100;
            }
        }
        $df = array();
        foreach ($notes as $note) {
            $df[] = (array)$note;
        }
        $datasets = [
            [
                "label" => "miss",
                "data"                 => [],
                'pointBackgroundColor' => '#f55142'
            ],
            [
                "label" => "ok",
                "data"                 => [],
                'pointBackgroundColor' => '#42c8f5'
            ],
            [
                "label" => "perfect",
                "data"                 => [],
                'pointBackgroundColor' => '#42f581'

            ]
        ];
        foreach ($df as $k=>$note) {
            $note['x'] = $note['_time'];
            $note['y'] = $raw_data[$k];
            unset($note['_time']);
            unset($note['_lineLayer']);
            unset($note['_lineIndex']);
            unset($note['_type']);
            unset($note['_cutDirection']);
            if ($note['y'] == -100) {
                $datasets[0]['data'][] = $note;
            } elseif ($note['y'] <= -15 || $note['y'] >= 15) {
                $datasets[1]['data'][] = $note;
            } else {
                $datasets[2]['data'][] = $note;
            }
        }
        return (['datasets' => $datasets]);
    }

}

