<?php

namespace App\Service;

use App\Entity\DifficultyRank;
use App\Entity\Overlay;
use App\Entity\Season;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongFeedback;
use App\Entity\SongHash;
use App\Entity\Utilisateur;
use App\Helper\AIMapper;
use App\Repository\DifficultyRankRepository;
use App\Repository\OverlayRepository;
use App\Repository\SeasonRepository;
use App\Repository\SongRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Format\Audio\Vorbis;
use FFMpeg\Format\Video\Ogg;
use Symfony\Component\Form\FormInterface;
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
    /**
     * @var DiscordService
     */
    private $discordService;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em, MailerInterface $mailer, DiscordService $discordService)
    {
        $this->kernel = $kernel;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->discordService = $discordService;
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
        $songHash = $this->em->getRepository(SongHash::class)->findOneBy(['hash' => $feedback->getHash()]);
        if ($songHash != null) {
            $song = $songHash->getSong();
            $email = (new Email())
                ->from('contact@ragnacustoms.com')
                ->to('pierrick.pobelle@gmail.com')
                ->subject('New feedback for ' . $song->getName() . '!');
            $email->html("New feedback ");
            $this->mailer->send($email);
        }
    }

    public function newFeedbackForMapper(SongFeedback $feedback)
    {
        /** @var SongHash $songHash */
        $songHash = $this->em->getRepository(SongHash::class)->findOneBy(['hash' => $feedback->getHash()]);
        if ($songHash != null) {
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
     * @throws NonUniqueResultException
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
        return $result['nb'] ?? 0;
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

    public function getCurrentSeason()
    {
        return $this->em->getRepository(Season::class)->getCurrent();
    }

    public function processFile(FormInterface $form, Song $song, bool $isWip = false)
    {
        $allowedFiles = [
            'preview.ogg',
            'info.dat',
            'Info.dat',
        ];
        $finalFolder = $this->kernel->getProjectDir() . "/public/songs-files/";
        $folder = $this->kernel->getProjectDir() . "/public/tmp-song/";
        $unzipFolder = $folder . uniqid();
        $file = $form->get('zipFile')->getData();
        $file->move($unzipFolder, $file->getClientOriginalName());
        $zip = new ZipArchive();
        $theZip = $unzipFolder . "/" . $file->getClientOriginalName();
        /** @var UploadedFile $file */
        if ($zip->open($theZip) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $elt = $this->remove_utf8_bom($zip->getFromIndex($i));
                $exp = explode("/", $filename);
                if (end($exp) != "") {
                    $fileinfo = pathinfo($filename);
                    if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                        $result = file_put_contents($unzipFolder . "/" . strtolower($fileinfo['basename']), $elt);
                    } else {
                        $result = file_put_contents($unzipFolder . "/" . $fileinfo['basename'], $elt);
                    }
                }
            }
            $zip->close();
        }
        $file = $unzipFolder . "/info.dat";
        if (!file_exists($file)) {
            $file = $unzipFolder . "/Info.dat";
            if (!file_exists($file)) {
                $this->rrmdir($unzipFolder);
                throw new Exception("The file seems to not be valid, at least info.dat is missing.");
            }
        }
        $content = file_get_contents($file);
        $json = json_decode($content);
        if ($json == null) {
            $this->rrmdir($unzipFolder);
            throw new Exception("WTF? I can't read your info.dat please check the file encoding.");
        }
        $allowedFiles[] = $json->_coverImageFilename;
        $allowedFiles[] = $json->_songFilename;


        $new = $song->getId() == null || $isWip != $song->getWip();
        foreach ($song->getSongDifficulties() as $difficulty) {
            foreach ($difficulty->getSeasons() as $season) {
                if ($season->isActive()) {
                    $this->rrmdir($unzipFolder);
                    throw new Exception("This song is used for this season ranking, you can't update it for now, come back a the end of the season..");
                }
            }
        }


        if ($form->get('description')->getData() != null) {
            preg_match('~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~', $form->get('description')->getData(), $match);
            if (count($match) > 0) {
                $song->setYoutubeLink($match[0]);
            } else {
                $song->setYoutubeLink(null);
            }
            $song->setDescription($form->get('description')->getData());
        }
        if ($form->get('youtubeLink')->getData() != null) {
            if (preg_match('~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~', $form->get('youtubeLink')->getData())) {
                $song->setYoutubeLink($form->get('youtubeLink')->getData());
            } else {

            }
        }
        if (!isset($json->_songApproximativeDuration) || empty($json->_songApproximativeDuration)) {
            $this->rrmdir($unzipFolder);
            throw new Exception("\"_songApproximativeDuration\" is missing in the info.dat file!");
        }

        $song->setVersion($json->_version);
        $song->setName(trim($json->_songName));
        $song->setLastDateUpload(new DateTime());
        $song->setSubName($json->_songSubName);
        $song->setAuthorName($json->_songAuthorName);
        $song->setLevelAuthorName($json->_levelAuthorName);
        $song->setBeatsPerMinute($json->_beatsPerMinute);
        $song->setShuffle($json->_shuffle);
        $song->setShufflePeriod($json->_shufflePeriod);
        $song->setPreviewStartTime($json->_previewStartTime);
        $song->setPreviewDuration($json->_previewDuration);
        $song->setApproximativeDuration($json->_songApproximativeDuration);
        $song->setFileName($json->_songFilename);
        $song->setCoverImageFileName($json->_coverImageFilename);
        $song->setEnvironmentName($json->_environmentName);
        $song->setModerated(true);

        $this->em->persist($song);

        foreach ($song->getSongDifficulties() as $difficulty) {
            $overlays = $this->em->getRepository(OverlayRepository::class)->findBy(["difficulty" => $difficulty]);
            /** @var Overlay $overlay */
            foreach ($overlays as $overlay) {
                $overlay->setDifficulty(null);
            }
            $difficulty->setSong(null);
            $this->em->remove($difficulty);
        }

        foreach (($json->_difficultyBeatmapSets[0])->_difficultyBeatmaps as $difficulty) {
            $diff = new SongDifficulty();
            $diff->setSong($song);
            $diff->setDifficultyRank($this->em->getRepository(DifficultyRank::class)->findOneBy(["level" => $difficulty->_difficultyRank]));
            $diff->setDifficulty($difficulty->_difficulty);
            $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
            $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);
            $song->addSongDifficulty($diff);
            $this->em->persist($diff);
            $allowedFiles[] = $difficulty->_beatmapFilename;
            $file = $difficulty->_beatmapFilename;

            $file = $unzipFolder . "/" . $file;
            $json2 = json_decode(file_get_contents($file));
            $diff->setNotesCount(count($json2->_notes));
            $diff->setNotePerSecond($diff->getNotesCount() / $song->getApproximativeDuration());

        }
        if ($isWip != $song->getWip()) {
            $song->setCreatedAt(new DateTime());
        }
        $this->em->flush();

        /** @var UploadedFile $file */
        $patterns_flattened = implode('|', $allowedFiles);
        $infolder = strtolower(preg_replace('/[^a-zA-Z]/', '', $song->getName()));
        $zip = new ZipArchive();
        if ($zip->open($theZip) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = ($zip->getNameIndex($i));
                if (!preg_match('/' . $patterns_flattened . '/', $filename, $matches) || preg_match('/autosaves/', $filename, $matches)) {
                    $zip->deleteName($filename);
                } else {
                    $newfilename = ($zip->getNameIndex($i));
                    $filename = ($zip->getNameIndex($i));
                    if (preg_match("/Info\.dat/", $newfilename)) {
                        $newfilename = strtolower($filename);
                    }
                    $x = explode('/', $newfilename);
                    $zip->renameName($filename, $infolder . "/" . $x[count($x) - 1]);
                }
            }
            $zip->close();
        }

        copy($theZip, $finalFolder . $song->getId() . ".zip");
        copy($unzipFolder . "/" . $json->_coverImageFilename, $this->kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());

        if ($this->kernel->getEnvironment() != "dev") {
            if ($song->getWip()) {
                $this->discordService->sendWipSongMessage($song);
            } elseif ($new) {
                $this->discordService->sendNewSongMessage($song);
            } else {
                $this->discordService->sendUpdatedSongMessage($song);
            }
        }

        $this->emulatorFileDispatcher($song, true);

        $this->rrmdir($unzipFolder);

        return true;

    }


    function remove_utf8_bom($text)
    {
        return $this->stripUtf16Le($this->stripUtf16Be($this->stripUtf8Bom($text)));//mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    function stripUtf8Bom($string)
    {
        return preg_replace('/^\xef\xbb\xbf/', '', $string);
    }

    function stripUtf16Le($string)
    {
        return preg_replace('/^\xff\xfe/', '', $string);
    }

    function stripUtf16Be($string)
    {
        return preg_replace('/^\xfe\xff/', '', $string);
    }

}

