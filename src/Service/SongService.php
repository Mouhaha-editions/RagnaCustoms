<?php

namespace App\Service;

use App\Entity\DifficultyRank;
use App\Entity\Overlay;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongHash;
use App\Entity\SongRequest;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use FFMpeg\FFProbe;
use Intervention\Image\ImageManagerStatic as Image;
use Pkshetlie\PhpUE\FCrc;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\VarDumper\VarDumper;
use ZipArchive;

class SongService
{
    /**
     * @var DiscordService
     */
    private $discordService;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var MailerInterface
     */
    private $mailer;
    /**
     * @var ScoreService
     */
    private $scoreService;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em, MailerInterface $mailer, DiscordService $discordService, ScoreService $scoreService)
    {
        $this->kernel = $kernel;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->discordService = $discordService;
        $this->scoreService = $scoreService;
    }

    public function emailRequestDone(SongRequest $songRequest, Song $song)
    {
        $email = (new Email())->from('contact@ragnacustoms.com')->to($songRequest->getRequestedBy()->getEmail())->subject('Your Map request ' . $song->getName() . ' was done');
        $email->html("Your Map request " . $song->getName() . " was done, you  can download it at https://ragnacustoms.com/song/detail/" . $song->getId());
        $this->mailer->send($email);

    }

    public function newFeedbackForMapper(Vote $feedback)
    {
        /** @var SongHash $songHash */
        $songHash = $this->em->getRepository(SongHash::class)->findOneBy(['hash' => $feedback->getHash()]);
        if ($songHash != null) {
            $song = $songHash->getSong();
            $mapper = $song->getUser();

            $email = (new Email())->from('contact@ragnacustoms.com')->to($mapper->getEmail())->addBcc("pierrick.pobelle@gmail.com")->subject('[Ragnacustoms.com] New feedback for ' . $song->getName() . '!');

            $email->html("Hi " . $mapper->getUsername() . ",<br/>You get a new feedback for " . $song->getName() . "!<br/><br/>You can read it at https://ragnacustoms.com/song/detail/" . $song->getId() . "#feedback<br/><br/>See you soon,<br/> The Staff");
            $this->mailer->send($email);
        }
    }

    /**
     * @param Song $song
     * @return int
     * @throws NonUniqueResultException
     */
    public function countVotePublic(Song $song)
    {
        $hashes = array_map(function (SongHash $hash) {
            return $hash->getHash();
        }, $song->getSongHashes()->toArray());
        $result = $this->em->getRepository(Vote::class)->createQueryBuilder('f')->select("COUNT(f) AS nb")->where('f.hash IN (:hashes)')->andWhere('f.isPublic = true')->andWhere('f.isModerated = true')->setParameter('hashes', $hashes)->getQuery()->getOneOrNullResult();
        return $result['nb'] ?? 0;
    }

    /**
     * @param Utilisateur|null $user
     * @param Song $song
     * @return Collection|Vote[]
     */
    public function getVotePublicOrMine(?Utilisateur $user, Song $song)
    {
        return $this->em->getRepository(Vote::class)->createQueryBuilder('f')->where('(f.song = :song AND f.isPublic = true AND f.isModerated = true AND f.feedback is not null)')->orWhere('(f.song = :song AND f.user = :user AND f.feedback is not null)')->setParameter('song', $song)->setParameter('user', $user)->getQuery()->getResult();
    }

    /**
     * @param FormInterface $form
     * @param Song $song
     * @param bool $isWip
     * @return bool
     * @throws Exception
     */
    public function processFile(?FormInterface $form, Song $song, bool $isWip = false)
    {
        $allowedFiles = [
            'preview.ogg',
            'info.dat',
            'Info.dat',
        ];
        $finalFolder = $this->kernel->getProjectDir() . "/public/songs-files/";
        $folder = $this->kernel->getProjectDir() . "/public/tmp-song/";
        $unzipFolder = $folder . uniqid();
        @mkdir($unzipFolder);
        if ($form != null) {
            $file = $form->get('zipFile')->getData();
            $file->move($unzipFolder, $file->getClientOriginalName());
            $unzippableFile = $unzipFolder . "/" . $file->getClientOriginalName();
        } else {
            $unzippableFile = $finalFolder . $song->getId() . ".zip";
        }
        $zip = new ZipArchive();
        $theZip = $unzippableFile;
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
            if ($difficulty->isRanked()) {
                $this->rrmdir($unzipFolder);
                throw new Exception("This song is ranked, you can't update it for now, please contact us.");
            }
        }


        if ($form != null && $form->get('description')->getData() != null) {
            preg_match('~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~', $form->get('description')->getData(), $match);
            if (count($match) > 0) {
                $song->setYoutubeLink($match[0]);
            } else {
                $song->setYoutubeLink(null);
            }
            $song->setDescription($form->get('description')->getData());
        }
        if ($form != null && $form->get('youtubeLink')->getData() != null) {
            if (preg_match('~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~', $form->get('youtubeLink')->getData())) {
                $song->setYoutubeLink($form->get('youtubeLink')->getData());
            } else {

            }
        }
        if (!isset($json->_songApproximativeDuration) || empty($json->_songApproximativeDuration)) {
            $this->rrmdir($unzipFolder);
            throw new Exception("\"_songApproximativeDuration\" is missing in the info.dat file!");
        }

        if ($form != null && empty($json->_coverImageFilename)) {
            throw new Exception("Cover is missing, please fix it and upload again");
        }

        $song->setVersion($json->_version);
        $song->setName(trim($json->_songName));
        $song->setLastDateUpload(new DateTime());
        $song->setSubName($json->_songSubName);
        $song->setIsExplicit(isset($json->_explicit) ? $json->_explicit == "true" : false);
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
            $overlays = $this->em->getRepository(Overlay::class)->findBy(["difficulty" => $difficulty]);
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
            $diff->setIsRanked((bool)$diff->isRanked());
            $diff->setDifficultyRank($this->em->getRepository(DifficultyRank::class)->findOneBy(["level" => $difficulty->_difficultyRank]));
            $diff->setDifficulty($difficulty->_difficulty);
            $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
            $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);
            $jsonContent = file_get_contents($unzipFolder . "/" . $difficulty->_beatmapFilename);
            $json2 = json_decode($jsonContent);
            $diff->setNotesCount(count($json2->_notes));
            $diff->setNotePerSecond($diff->getNotesCount() / $song->getApproximativeDuration());
            $diff->setTheoricalMaxScore($this->calculateTheoricalMaxScore($diff));
            $song->addSongDifficulty($diff);
            $this->em->persist($diff);
            $allowedFiles[] = $difficulty->_beatmapFilename;
            $diff->setWanadevHash(Fcrc::StrCrc32($jsonContent));

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

        @copy($theZip, $finalFolder . $song->getId() . ".zip");
        @copy($unzipFolder . "/" . $json->_coverImageFilename, $this->kernel->getProjectDir() . "/public/covers/" . $song->getId() . $song->getCoverImageExtension());
        if (!$song->hasCover()) {
            $song->setWip(true);
        }
        if ($this->kernel->getEnvironment() != "dev") {
            if ($song->getWip()) {
                $this->discordService->sendWipSongMessage($song);
            } elseif ($new) {
                $this->discordService->sendNewSongMessage($song);
            } else {
                $this->discordService->sendUpdatedSongMessage($song);
            }
        }
        if ($form !== null) {
            $this->emulatorFileDispatcher($song, true);
//        $this->coverOptimisation($song);
        }
        $this->rrmdir($unzipFolder);

        return true;

    }

    function remove_utf8_bom($text)
    {
        return $this->stripUtf16Le($this->stripUtf16Be($this->stripUtf8Bom($text)));//mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
    }

    function stripUtf16Le($string)
    {
        return preg_replace('/^\xff\xfe/', '', $string);
    }

    function stripUtf16Be($string)
    {
        return preg_replace('/^\xfe\xff/', '', $string);
    }

    function stripUtf8Bom($string)
    {
        return preg_replace('/^\xef\xbb\xbf/', '', $string);
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object); else
                        @unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    public function calculateTheoricalMaxScore(SongDifficulty $diff)
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

        while ($consumedNotes <= $noteCount) {
            $combo = $combo + 1;
            $consumedNotes = $consumedNotes + (2 * (15 + 10 * $combo));

            $maxYellowCombo = $combo - 1;
        }

        // Score calculation based on Wanadev public formula (unit is the distance traveled by the boat):
        //   base speed * duration of the song
        // + 1/4 of the base speed for each note for 0.3 second
        // - 1/4 of the base speed for each miss note for 0.3 second
        // + Number of blue combos * base speed for 0.75 second
        // + Number of yellow combos * base speed for 3 second

        $theoricalMaxScore = ($baseSpeed * $duration) + ($noteCount * 0.3 * $baseSpeed / 4) - ($miss * 0.3 * $baseSpeed / 4) + ($maxBlueCombo * 3 / 4 * $baseSpeed) + ($maxYellowCombo * 3 * $baseSpeed);

        return round($theoricalMaxScore, 2);
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

    private function wannadev_crc32($str)
    {
        $CRCTablesSB8 = array(
            0x00000000,
            0x77073096,
            0xee0e612c,
            0x990951ba,
            0x076dc419,
            0x706af48f,
            0xe963a535,
            0x9e6495a3,
            0x0edb8832,
            0x79dcb8a4,
            0xe0d5e91e,
            0x97d2d988,
            0x09b64c2b,
            0x7eb17cbd,
            0xe7b82d07,
            0x90bf1d91,
            0x1db71064,
            0x6ab020f2,
            0xf3b97148,
            0x84be41de,
            0x1adad47d,
            0x6ddde4eb,
            0xf4d4b551,
            0x83d385c7,
            0x136c9856,
            0x646ba8c0,
            0xfd62f97a,
            0x8a65c9ec,
            0x14015c4f,
            0x63066cd9,
            0xfa0f3d63,
            0x8d080df5,
            0x3b6e20c8,
            0x4c69105e,
            0xd56041e4,
            0xa2677172,
            0x3c03e4d1,
            0x4b04d447,
            0xd20d85fd,
            0xa50ab56b,
            0x35b5a8fa,
            0x42b2986c,
            0xdbbbc9d6,
            0xacbcf940,
            0x32d86ce3,
            0x45df5c75,
            0xdcd60dcf,
            0xabd13d59,
            0x26d930ac,
            0x51de003a,
            0xc8d75180,
            0xbfd06116,
            0x21b4f4b5,
            0x56b3c423,
            0xcfba9599,
            0xb8bda50f,
            0x2802b89e,
            0x5f058808,
            0xc60cd9b2,
            0xb10be924,
            0x2f6f7c87,
            0x58684c11,
            0xc1611dab,
            0xb6662d3d,
            0x76dc4190,
            0x01db7106,
            0x98d220bc,
            0xefd5102a,
            0x71b18589,
            0x06b6b51f,
            0x9fbfe4a5,
            0xe8b8d433,
            0x7807c9a2,
            0x0f00f934,
            0x9609a88e,
            0xe10e9818,
            0x7f6a0dbb,
            0x086d3d2d,
            0x91646c97,
            0xe6635c01,
            0x6b6b51f4,
            0x1c6c6162,
            0x856530d8,
            0xf262004e,
            0x6c0695ed,
            0x1b01a57b,
            0x8208f4c1,
            0xf50fc457,
            0x65b0d9c6,
            0x12b7e950,
            0x8bbeb8ea,
            0xfcb9887c,
            0x62dd1ddf,
            0x15da2d49,
            0x8cd37cf3,
            0xfbd44c65,
            0x4db26158,
            0x3ab551ce,
            0xa3bc0074,
            0xd4bb30e2,
            0x4adfa541,
            0x3dd895d7,
            0xa4d1c46d,
            0xd3d6f4fb,
            0x4369e96a,
            0x346ed9fc,
            0xad678846,
            0xda60b8d0,
            0x44042d73,
            0x33031de5,
            0xaa0a4c5f,
            0xdd0d7cc9,
            0x5005713c,
            0x270241aa,
            0xbe0b1010,
            0xc90c2086,
            0x5768b525,
            0x206f85b3,
            0xb966d409,
            0xce61e49f,
            0x5edef90e,
            0x29d9c998,
            0xb0d09822,
            0xc7d7a8b4,
            0x59b33d17,
            0x2eb40d81,
            0xb7bd5c3b,
            0xc0ba6cad,
            0xedb88320,
            0x9abfb3b6,
            0x03b6e20c,
            0x74b1d29a,
            0xead54739,
            0x9dd277af,
            0x04db2615,
            0x73dc1683,
            0xe3630b12,
            0x94643b84,
            0x0d6d6a3e,
            0x7a6a5aa8,
            0xe40ecf0b,
            0x9309ff9d,
            0x0a00ae27,
            0x7d079eb1,
            0xf00f9344,
            0x8708a3d2,
            0x1e01f268,
            0x6906c2fe,
            0xf762575d,
            0x806567cb,
            0x196c3671,
            0x6e6b06e7,
            0xfed41b76,
            0x89d32be0,
            0x10da7a5a,
            0x67dd4acc,
            0xf9b9df6f,
            0x8ebeeff9,
            0x17b7be43,
            0x60b08ed5,
            0xd6d6a3e8,
            0xa1d1937e,
            0x38d8c2c4,
            0x4fdff252,
            0xd1bb67f1,
            0xa6bc5767,
            0x3fb506dd,
            0x48b2364b,
            0xd80d2bda,
            0xaf0a1b4c,
            0x36034af6,
            0x41047a60,
            0xdf60efc3,
            0xa867df55,
            0x316e8eef,
            0x4669be79,
            0xcb61b38c,
            0xbc66831a,
            0x256fd2a0,
            0x5268e236,
            0xcc0c7795,
            0xbb0b4703,
            0x220216b9,
            0x5505262f,
            0xc5ba3bbe,
            0xb2bd0b28,
            0x2bb45a92,
            0x5cb36a04,
            0xc2d7ffa7,
            0xb5d0cf31,
            0x2cd99e8b,
            0x5bdeae1d,
            0x9b64c2b0,
            0xec63f226,
            0x756aa39c,
            0x026d930a,
            0x9c0906a9,
            0xeb0e363f,
            0x72076785,
            0x05005713,
            0x95bf4a82,
            0xe2b87a14,
            0x7bb12bae,
            0x0cb61b38,
            0x92d28e9b,
            0xe5d5be0d,
            0x7cdcefb7,
            0x0bdbdf21,
            0x86d3d2d4,
            0xf1d4e242,
            0x68ddb3f8,
            0x1fda836e,
            0x81be16cd,
            0xf6b9265b,
            0x6fb077e1,
            0x18b74777,
            0x88085ae6,
            0xff0f6a70,
            0x66063bca,
            0x11010b5c,
            0x8f659eff,
            0xf862ae69,
            0x616bffd3,
            0x166ccf45,
            0xa00ae278,
            0xd70dd2ee,
            0x4e048354,
            0x3903b3c2,
            0xa7672661,
            0xd06016f7,
            0x4969474d,
            0x3e6e77db,
            0xaed16a4a,
            0xd9d65adc,
            0x40df0b66,
            0x37d83bf0,
            0xa9bcae53,
            0xdebb9ec5,
            0x47b2cf7f,
            0x30b5ffe9,
            0xbdbdf21c,
            0xcabac28a,
            0x53b39330,
            0x24b4a3a6,
            0xbad03605,
            0xcdd70693,
            0x54de5729,
            0x23d967bf,
            0xb3667a2e,
            0xc4614ab8,
            0x5d681b02,
            0x2a6f2b94,
            0xb40bbe37,
            0xc30c8ea1,
            0x5a05df1b,
            0x2d02ef8d
        );

        $CRC = 0xFFFFFFFF;

        for ($i = 0; $i < strlen($str); $i++) {
            $chr = ord($str[$i]);
            $CRC = $CRCTablesSB8[($chr ^ $CRC) & 0xff] ^ ($CRC >> 8);
            $chr = ($chr >> 8) & 0xFF;
            $CRC = ($CRCTablesSB8[($chr ^ $CRC) & 0xff] ^ ($CRC >> 8));
            $chr = ($chr >> 8) & 0xFF;
            $CRC = ($CRCTablesSB8[($chr ^ $CRC) & 0xff] ^ ($CRC >> 8));
            $chr = ($chr) & 0xFF;
            $CRC = ($CRCTablesSB8[($chr ^ $CRC) & 0xff] ^ ($CRC >> 8));
        }


        return $CRC ^ 0xFFFFFFFF;
    }

    public function processExistingFile(Song $song)
    {
        try {
            $finalFolder = $this->kernel->getProjectDir() . "/public/songs-files/";
            $folder = $this->kernel->getProjectDir() . "/public/tmp-song/";
            $unzipFolder = $folder . uniqid();
            @mkdir($unzipFolder);
            $unzippableFile = $finalFolder . $song->getId() . ".zip";
            $zip = new ZipArchive();
            /** @var UploadedFile $file */
            if ($zip->open($unzippableFile) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $elt = $this->remove_utf8_bom($zip->getFromIndex($i));
                    $exp = explode("/", $filename);
                    if (end($exp) != "") {
                        $fileinfo = pathinfo($filename);
                        if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                            file_put_contents($unzipFolder . "/" . strtolower($fileinfo['basename']), $elt);
                        } else {
                            file_put_contents($unzipFolder . "/" . $fileinfo['basename'], $elt);
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

            foreach (($json->_difficultyBeatmapSets[0])->_difficultyBeatmaps as $difficulty) {
                $rank = $this->em->getRepository(DifficultyRank::class)->findOneBy(["level" => $difficulty->_difficultyRank]);
                $diff = $this->em->getRepository(SongDifficulty::class)->findOneBy([
                    'song' => $song,
                    'difficultyRank' => $rank
                ]);
                if ($diff == null) {
                    echo $song->getName() . " " . $rank->getLevel() . " non trouvée\r\n";
                }
                $diff->setIsRanked((bool)$diff->isRanked());
                $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
                $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);
                $jsonContent = file_get_contents($unzipFolder . "/" . $difficulty->_beatmapFilename);
                $diff->setTheoricalMaxScore($this->calculateTheoricalMaxScore($diff));
                $diff->setWanadevHash(Fcrc::StrCrc32($jsonContent));
                $this->em->flush();

            }

            $this->em->flush();
            $this->rrmdir($unzipFolder);
            return true;
        } catch (Exception $error) {
            echo "\r\n[Erreur]" . $song->getName() . ": " . $error->getMessage() . "\r\n\r\n";
        }
        return false;
    }

    public function getLastSongsPlayed($count)
    {
        return $this->em->getRepository(Song::class)->createQueryBuilder('s')->leftJoin('s.songHashes', 'song_hashes')->leftJoin(Score::class, 'score', Join::WITH, 'score.hash = song_hashes.hash')->orderBy('score.updatedAt', 'DESC')->setFirstResult(0)->setMaxResults($count)->getQuery()->getResult();

    }

    public function evaluateFile(FormInterface $form)
    {
        $allowedFiles = [
            'preview.ogg',
            'info.dat',
            'Info.dat',
        ];
        $folder = $this->kernel->getProjectDir() . "/public/tmp-evaluator/";
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

        if (!isset($json->_songApproximativeDuration) || empty($json->_songApproximativeDuration)) {
            $this->rrmdir($unzipFolder);
            throw new Exception("\"_songApproximativeDuration\" is missing in the info.dat file!");
        }
        $result = $this->scoreService->calculateDifficulties($file);
        $this->rrmdir($unzipFolder);

        return $result;

    }

    private function coverOptimisation(Song $song)
    {

        $cdir = scandir($this->kernel->getProjectDir() . "/public/covers");
        foreach ($cdir as $key => $value) {
            if ($value == "." || $value == "..") {
                continue;
            }
            try {
                $filedir = $this->kernel->getProjectDir() . "/public/covers/" . $value;

                $image = Image::make($filedir);

                $background = Image::canvas(349, 349, 'rgba(255, 255, 255, 0)');

                if ($image->width() >= $image->height()) {
                    $image->widen(349);
                } else {
                    $image->heighten(349);
                }
                $background->insert($image, 'center-center');
                $background->save($filedir);
            } catch (Exception $exception) {

            }
        }

    }

    public function count($addWip = false)
    {
        $qb = $this->em->getRepository(Song::class)->createQueryBuilder('s')->where('s.isDeleted != true')->select('COUNT(s.id) AS count')->setFirstResult(0)->setMaxResults(1);
        if (!$addWip) {
            $qb->andWhere('s.wip != true');
        }
        $songs = $qb->getQuery()->getOneOrNullResult();
        return $songs['count'];
    }

    public function getSimilarSongs(Song $song, $max = 10)
    {
        return $this->em->getRepository(Song::class)->createQueryBuilder('s')->distinct()->leftJoin('s.categoryTags', 'category_tags')->where("category_tags.id IN (:categories)")->andWhere('s.id != :song')->setParameter('categories', $song->getCategoryTags())->setParameter('song', $song)
//            ->orderBy('s')
            ->setMaxResults($max)->setFirstResult(0)->getQuery()->getResult();
    }


    public function getLeaderboardPosition(UserInterface $user, SongDifficulty $songDifficulty)
    {
        $mine = $this->em->getRepository(Score::class)->findOneBy([
            'user' => $user,
            'songDifficulty' => $songDifficulty
        ]);
        if ($mine == null) {
            return "-";
        }
        return count($this->em->getRepository(Score::class)->createQueryBuilder("s")->select('s.id')->where('s.rawPP > :my_score')->andWhere('s.songDifficulty = :difficulty')->andWhere('s.user != :me')->setParameter('my_score', $mine->getRawPP())->setParameter('difficulty', $songDifficulty)->setParameter('me', $user)->groupBy('s.user')->getQuery()->getResult()) + 1;


    }
}

