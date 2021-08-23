<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\SongFeedback;
use App\Entity\SongHash;
use App\Entity\Utilisateur;
use App\Helper\AIMapper;
use App\Repository\SongRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Audio\Vorbis;
use FFMpeg\Format\Video\Ogg;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\VarDumper\VarDumper;
use ZipArchive;

class SongService
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em, MailerInterface $mailer)
    {
        $this->kernel = $kernel;
        $this->em = $em;
        $this->mailer = $mailer;
    }

    public function AiMap()
    {
        $file = $this->kernel->getProjectDir() . "/public/song.ogg";
        $ffprobe = FFProbe::create();
        $ffmpeg = FFMpeg::create();
        $audio = $ffmpeg->open($file);
        $probe = $ffprobe->format($file);
        $durationMp3 = (int)($probe->get('duration'));
        $bpm = 140;
        $ratio = 20;
        $level = 7;
        $durationbpm = round($bpm / 60 * $durationMp3, 0) * $ratio;
        $waveform = $audio->waveform($durationbpm, round(($durationbpm * 9) / 25), array('#00FF00'));
        $waveform->save($this->kernel->getProjectDir() . "/public/waveform.png");
        $ai = new AIMapper($this->kernel->getProjectDir() . "/public/waveform.png", $durationMp3, $bpm, $ratio, $level);
        $result = $ai->read();

        return $ai->map($result, "C:\Users\pierr\Documents\Ragnarock\CustomSongs\otherworld\Level" . $level . ".dat");
    }

    public function emulatorFileDispatcher(Song $song, bool $force = false)
    {
        if ($song->getInfoDatFile() !== null && !$force) {
            return null;
        }
        $file = $this->kernel->getProjectDir() . "/public/songs-files/" . $song->getId() . ".zip";
        $uniqBeat = "/ragna-beat/" . uniqid();
        $unzipFolder = $this->kernel->getProjectDir() . "/public" . $uniqBeat;
        mkdir($this->kernel->getProjectDir() . "/public" . $uniqBeat);
        $zip = new ZipArchive();
//        try {

        $files = [];
        $getpreview = false;
        $previewFile = "";
        $previewLocalnameFile = "";
        $songfile = "";
        try {
            if ($zip->open($file) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {

                    $filename = $zip->getNameIndex($i);
                    $elt = $zip->getFromIndex($i);
                    $exp = explode("/", $filename);
                    if (end($exp) != "") {
                        $fileinfo = pathinfo($filename);
                        $result = file_put_contents($unzipFolder . "/" . $fileinfo['basename'], $elt);
                        if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                            $zip->renameName($filename, strtolower($filename));
                            $song->setInfoDatFile($uniqBeat . "/" . $fileinfo['basename']);
                        }
                        if (preg_match("#\.ogg#isU", $fileinfo['basename'])) {
                            if (preg_match("#preview\.ogg#isU", $fileinfo['basename'])) {
                                $getpreview = true;
                            } else {
                                $songfile = $this->kernel->getProjectDir() . "/public" . $uniqBeat . "/" . $fileinfo['basename'];
                                $previewFile = $this->kernel->getProjectDir() . "/public" . $uniqBeat . "/preview.ogg";
                                $previewLocalnameFile = $exp[0] . '/preview.ogg';
                            }
                        }
                        if (preg_match("#\.dat#isU", $fileinfo['basename'])) {
                            $files[] = $this->kernel->getProjectDir() . "/public" . $uniqBeat . "/" . $fileinfo['basename'];
                        }
                    }
                }
//                $filename = $song->getInfoDatFile();
//                $song->setGuid(md5_file($this->kernel->getProjectDir() . "/public/" . $filename));
//                $song->
                $hash = $this->HashSong($files);

                if ($song->getNewGuid() !== $hash) {
                    $version = $this->em->getRepository(SongHash::class)->getLastVersion($song) + 1;
                    $newHash = new SongHash();
                    $newHash->setSong($song);
                    $newHash->setHash($hash);
                    $newHash->setVersion($version);
                    $song->addSongHash($newHash);
                    $this->em->persist($newHash);
                }

                $song->setNewGuid($hash);
                $this->em->flush();

                if (!$getpreview) {
                    $ffprobe = FFProbe::create([
                        'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                        'ffprobe.binaries' => '/usr/bin/ffprobe'
                    ]);
                    $probe = $ffprobe->format($songfile);
                    $durationMp3 = (int)($probe->get('duration') / 2);
                    exec('ffmpeg -y -i "' . $songfile . '"  -ss ' . $durationMp3 . ' -t 5 -c:a copy -b:a 96k "' . $previewFile . '"');

                    $zip->addFile($previewFile, $previewLocalnameFile);
                }
                $zip->close();
            }
        } catch (Exception $e) {
            VarDumper::dump($song->getId());
        }

    }

    public function newFeedback(SongFeedback $feedback)
    {
        /** @var SongHash $songHash */
        $songHash = $this->em->getRepository(SongHash::class)->findOneBy(['hash'=>$feedback->getHash()]);
        if($songHash != null) {
         $song = $songHash->getSong();
            $email = (new Email())
                ->from('contact@ragnacustoms.com')
                ->to('pierrick.pobelle@gmail.com')
                ->subject('New feedback for ' . $song->getName() . '!');
            $email->html("New feedback");
            $this->mailer->send($email);
        }
    }

    public function newFeedbackForMapper(SongFeedback $feedback)
    {
        /** @var SongHash $songHash */
        $songHash = $this->em->getRepository(SongHash::class)->findOneBy(['hash'=>$feedback->getHash()]);
        if($songHash != null) {
            $song = $songHash->getSong();
            $mapper = $song->getUser();

            $email = (new Email())
                ->from('contact@ragnacustoms.com')
                ->to($mapper->getEmail())
                ->addBcc("pierrick.pobelle@gmail.com")
                ->subject('[Ragnacustoms.com] New feedback for ' . $song->getName() . '!');
            $email->html("Hi " . $mapper->getUsername() . ",<br/>You get a new feedback for " . $song->getName() . "!<br/><br/>You can read it at https://ragnacustoms.com/song/detail/" . $song->getId() . "#feedback<br/><br/>See you soon,<br/> The Staff");
            $this->mailer->send($email);
        }
    }

    public function HashSong(array $files)
    {
        $md5s = [];
        foreach ($files as $file) {
            $md5s[] = md5_file($file);
        }
        sort($md5s);
        $str = implode('', $md5s);
        return md5($str);
    }

    public function getByHash($hash)
    {
        return $this->em->getRepository(Song::class)->findOneByHash($hash);
    }

    /**
     * @return Collection|SongFeedback[]
     */
    public function getSongFeedbackPublic(Song $song)
    {
        $hashes = array_map(function (SongHash $hash) {
            return $hash->getHash();
        }, $song->getSongHashes()->toArray());
        return $this->em->getRepository(SongFeedback::class)
            ->createQueryBuilder('f')
            ->where('f.hash IN (:hashes)')
            ->andWhere('f.isPublic = true')
            ->andWhere('f.isModerated = true')
            ->setParameter('hashes', $hashes)
            ->getQuery()->getResult();
    }

    /**
     * @param Song $song
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countSongFeedbackPublic(Song $song)
    {
        $hashes = array_map(function (SongHash $hash) {
            return $hash->getHash();
        }, $song->getSongHashes()->toArray());
        $result = $this->em->getRepository(SongFeedback::class)
            ->createQueryBuilder('f')
            ->select("COUNT(f) AS nb")->where('f.hash IN (:hashes)')
            ->andWhere('f.isPublic = true')
            ->andWhere('f.isModerated = true')
            ->setParameter('hashes', $hashes)
            ->getQuery()->getOneOrNullResult();
        return $result['nb']??0;
    }
    /**
     * @param Utilisateur|null $user
     * @param Song $song
     * @return Collection|SongFeedback[]
     */
    public function getSongFeedbackPublicOrMine(?Utilisateur $user, Song $song)
    {
        if ($user == null) {
            return $this->getSongFeedbackPublic($song);
        }
            $hashes = array_map(function (SongHash $hash) {
                return $hash->getHash();
            }, $song->getSongHashes()->toArray());
        return $this->em->getRepository(SongFeedback::class)->createQueryBuilder('f')
            ->where('(f.hash IN (:hashes) AND f.isPublic = true AND f.isModerated = true)')
            ->orWhere('(f.hash IN (:hashes) AND f.user = :user)')
            ->setParameter('hashes', $hashes)
            ->setParameter('user', $user)
            ->getQuery()->getResult();

    }
}

