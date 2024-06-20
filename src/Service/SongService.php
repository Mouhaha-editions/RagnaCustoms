<?php

namespace App\Service;

use App\Entity\DifficultyRank;
use App\Entity\FollowMapper;
use App\Entity\Notification;
use App\Entity\Overlay;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongHash;
use App\Entity\SongRequest;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use App\Entity\VoteCounter;
use App\Enum\ENotification;
use App\Repository\SongRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use FFMpeg\FFProbe;
use Intervention\Image\ImageManagerStatic as Image;
use Pkshetlie\PhpUE\FCrc;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ZipArchive;

class SongService
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly DiscordService $discordService,
        private readonly UrlGeneratorInterface $router,
        private readonly NotificationService $notificationService,
        private readonly SongRepository $songRepository,
        private readonly Security $security
    ) {
    }

    public function emailRequestDone(SongRequest $songRequest, Song $song)
    {
        $email = (new Email())->from('contact@ragnacustoms.com')->to(
            $songRequest->getRequestedBy()->getEmail()
        )->subject('Your Map request '.$song->getName().' was done');
        $email->html(
            "Your Map request ".$song->getName(
            )." was done, you  can download it at https://ragnacustoms.com/song/detail/".$song->getId()
        );
        $this->mailer->send($email);
    }

    public function newFeedbackForMapper(Vote $feedback)
    {
        /** @var SongHash $songHash */
        $songHash = $this->em->getRepository(SongHash::class)->findOneBy(['hash' => $feedback->getHash()]);
        if ($songHash != null) {
            $song = $songHash->getSong();
            $mappers = $song->getMappers();

            foreach ($mappers as $mapper) {
                $email = (new Email())->from('contact@ragnacustoms.com')->to($mapper->getEmail())->addBcc(
                    "pierrick.pobelle@gmail.com"
                )->subject('[Ragnacustoms.com] New feedback for '.htmlentities($song->getName()).'!');

                $email->html(
                    "Hi ".$mapper->getUsername().",<br/>You got a new feedback for ".htmlentities(
                        $song->getName()
                    )."!<br/><br/>You can read it at https://ragnacustoms.com/song/detail/".$song->getId(
                    )."#feedback<br/><br/>See you soon,<br/> The Staff"
                );
                $this->mailer->send($email);
            }
        }
    }

    /**
     * @param Song $song
     *
     * @return int
     * @throws NonUniqueResultException
     */
    public function countVotePublic(Song $song)
    {
        $hashes = array_map(function (SongHash $hash) {
            return $hash->getHash();
        }, $song->getSongHashes()->toArray());
        $result = $this->em->getRepository(Vote::class)->createQueryBuilder('f')
            ->select("COUNT(f) AS nb")
            ->where('f.hash IN (:hashes)')
            ->andWhere('f.isPublic = true')
            ->andWhere('f.isModerated = true')
            ->setParameter('hashes', $hashes)
            ->getQuery()->getOneOrNullResult();

        return $result['nb'] ?? 0;
    }

    public function getVotePublicOrMine(?Utilisateur $user, Song $song)
    {
        $qb = $this->em->getRepository(Vote::class)
            ->createQueryBuilder('f');
        $qb
            ->where(
                $qb->expr()->andX(
                    'f.song = :song',
                    'f.isPublic = true',
                    'f.isModerated = true',
                    'f.feedback is not null',
                )
            )
            ->orWhere(
                $qb->expr()->andX(
                    'f.song = :song',
                    'f.user = :user',
                    'f.feedback is not null',
                )
            )
            ->setParameter('song', $song)
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function isFeedbackDone(?Utilisateur $user, Song $song): bool
    {
        $qb = $this->em->getRepository(Vote::class)
            ->createQueryBuilder('f');
        $qb
            ->where(
                $qb->expr()->andX(
                    'f.song = :song',
                    'f.user = :user',
                )
            )
            ->setParameter('song', $song)
            ->setParameter('user', $user);

        return (bool)$qb->getQuery()->getResult();
    }

    public function getFileSize(Song $song)
    {
        $size = filesize($this->kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip");
        $sz = 'BKMGTP';
        $factor = floor((strlen($size) - 1) / 3);

        return sprintf("%.2f", $size / pow(1024, $factor)).@$sz[$factor];
    }

    public function getAdventCalendar()
    {
        return $this->em->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->where('s.lastDateUpload BETWEEN \'2022-12-01\' AND \'2022-12-26\' ')
            ->leftJoin('s.mappers', 'm')
            ->where('m.id = :user')
            ->setParameter('user', 29)
            ->getQuery()->getResult();
    }

    /**
     * @param FormInterface $form
     * @param Song $song
     * @param bool $isWip
     *
     * @return bool
     * @throws Exception
     */
    public function processFile(?FormInterface $form, Song $song, bool $isWip = false)
    {
        try {

            $finalFolder = $this->kernel->getProjectDir()."/public/songs-files/";
            $folder = $this->kernel->getProjectDir()."/public/tmp-song/";
            $unzipFolder = $folder.uniqid();
            @mkdir($unzipFolder);

            if ($form != null) {
                $file = $form->get('zipFile')->getData();
                $file->move($unzipFolder, $file->getClientOriginalName());
                $unzippableFile = $unzipFolder."/".$file->getClientOriginalName();
            } else {
                $unzippableFile = $finalFolder.$song->getId().".zip";
            }

            if ($form != null && $form->get('description')->getData() != null) {
                preg_match(
                    '~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~',
                    $form->get('description')->getData(),
                    $match
                );
                if (count($match) > 0) {
                    $song->setYoutubeLink($match[0]);
                } else {
                    $song->setYoutubeLink(null);
                }
                $song->setDescription($form->get('description')->getData());
            }

            if ($form != null && $form->get('youtubeLink')->getData() != null) {
                if (preg_match(
                    '~(?:https?://)?(?:www.)?(?:youtube.com|youtu.be)/(?:watch\?v=)?([^\s]+)~',
                    $form->get('youtubeLink')->getData()
                )) {
                    $song->setYoutubeLink($form->get('youtubeLink')->getData());
                }
            }

            $this->process($unzippableFile, $unzipFolder, $song, $isWip);

            if ($form !== null) {
                $this->emulatorFileDispatcher($song, true);
                $this->coverOptimisation($song);
            }
        } catch (Exception $e) {
            if (!$song->isPublished()) {
                $this->cleanUp($song);
            }
            throw new Exception($e->getMessage());
        } finally {
            $this->rrmdir($unzipFolder);
        }

        return true;
    }

    private function process(string $unzippableFile, string $unzipFolder, Song $song, bool $isWip = false)
    {
        $allowedFiles = [
            'preview.ogg',
            'info.dat',
            'Info.dat',
        ];
        $finalFolder = $this->kernel->getProjectDir()."/public/songs-files/";
        $zip = new ZipArchive();
        $theZip = $unzippableFile;
        /** @var UploadedFile $file */
        if ($zip->open($theZip) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $elt = $this->remove_utf8_bom($zip->getFromIndex($i));
                $exp = explode("/", $filename);
                if (end($exp) != "") {
                    $fileinfo = pathinfo($filename);
                    if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                        $result = file_put_contents($unzipFolder."/".strtolower($fileinfo['basename']), $elt);
                    } else {
                        $result = file_put_contents($unzipFolder."/".$fileinfo['basename'], $elt);
                    }
                }
            }
            $zip->close();
        }
        $file = $unzipFolder."/info.dat";
        if (!file_exists($file)) {
            $file = $unzipFolder."/Info.dat";
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
        if (!file_exists($unzipFolder."/".$json->_coverImageFilename)) {
            throw new Exception("The cover name doesn't match to the name in the info.dat.");
        }
        $allowedFiles[] = $json->_coverImageFilename;
        if ($json->_coverImageFilename == ".jpg" || empty($json->_coverImageFilename)) {
            throw new Exception("Cover is missing, please fix it and upload again");
        }
        if (strlen($json->_coverImageFilename) > 29) {
            throw new Exception("The cover name need to contain less than 25 chars.");
        }
        $allowedFiles[] = $json->_songFilename;
        list($width, $height) = getimagesize($unzipFolder."/".$json->_coverImageFilename);
        if ($width != $height) {
            throw new Exception("Cover have to be a square.");
        }

        $new = $song->getId() == null || $isWip != $song->getWip();
        if ($song->isRanked()) {
            $this->rrmdir($unzipFolder);
            throw new Exception("This song is ranked, you can't update it for now, please contact us.");
        }

        if (!isset($json->_songApproximativeDuration) || empty($json->_songApproximativeDuration)) {
            $this->rrmdir($unzipFolder);
            throw new Exception("\"_songApproximativeDuration\" is missing in the info.dat file!");
        }

        $songName = trim($json->_songName);
        $authorName = $json->_songAuthorName;
        $existingSong = $this->em->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->distinct()
            ->leftJoin('s.mappers', 'm')
            ->andWhere('s.name = :name')
            ->andWhere('s.authorName = :authorName')
            ->andWhere('m.id IN (:users)')
            ->setParameters([
                'name' => $songName,
                'authorName' => $authorName,
                'users' => $song->getMappers(),
            ])->getQuery()->getResult();
        if ($existingSong != null && $new === true) {
            throw new Exception("You already uploaded this song, please edit the last upload.");
        }
        $song->setVersion($json->_version);
        $song->setName($songName);
        if (!isset($json->_songSubName)) {
            throw new Exception("\"_songSubName\" is missing in the info.dat file!");
        }
        $song->setSubName($json->_songSubName);
        $song->setIsExplicit(isset($json->_explicit) ? $json->_explicit == "true" : false);
        $song->setAuthorName($authorName);
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
        $previousDiffs = [];

        foreach ($song->getSongDifficulties() as $difficulty) {
            $overlays = $this->em->getRepository(Overlay::class)->findBy(["difficulty" => $difficulty]);

            /** @var Overlay $overlay */
            foreach ($overlays as $overlay) {
                $overlay->setDifficulty(null);
            }

            $previousDiffs[$difficulty->getId()] = $difficulty;
        }

        foreach (($json->_difficultyBeatmapSets[0])->_difficultyBeatmaps as $difficulty) {
            $jsonContent = file_get_contents($unzipFolder."/".$difficulty->_beatmapFilename);
            $fcrc = Fcrc::StrCrc32($jsonContent);

            $rank = $this->em
                ->getRepository(DifficultyRank::class)
                ->findOneBy(["level" => $difficulty->_difficultyRank]);
            $diff = null;
            if ($song->getId() !== null) {
                /** @var SongDifficulty $diff */
                $diff = $this->em->getRepository(SongDifficulty::class)
                    ->createQueryBuilder('sd')
                    ->where('sd.wanadevHash = :fcrc')
                    ->setParameter('fcrc', $fcrc)
                    ->andWhere('sd.song = :song')
                    ->setParameter('song', $song)
                    ->getQuery()->setMaxResults(1)->setFirstResult(0)->getOneOrNullResult();
                if ($diff != null && $diff->getDifficultyRank() === $rank) {
                    unset($previousDiffs[$diff->getId()]);
                    $allowedFiles[] = $difficulty->_beatmapFilename;
                    continue;
                } else {
                    $diff = $this->em->getRepository(SongDifficulty::class)
                        ->createQueryBuilder('sd')
                        ->where('sd.difficultyRank = :rank')
                        ->setParameter('rank', $rank)
                        ->andWhere('sd.song = :song')
                        ->setParameter('song', $song)
                        ->getQuery()->setMaxResults(1)->setFirstResult(0)->getOneOrNullResult();
                }
            }
            if ($diff == null) {
                $diff = new SongDifficulty();
                $diff->setSong($song);
            } else {
                unset($previousDiffs[$diff->getId()]);
                $allowedFiles[] = $difficulty->_beatmapFilename;
            }
            foreach ($diff->getScores() as $score) {
                $this->em->remove($score);
            }
            foreach ($diff->getScoreHistories() as $score) {
                $this->em->remove($score);
            }
            foreach ($song->getDownloadCounters() as $download) {
                $this->em->remove($download);
            }
            $diff->setIsRanked((bool)$diff->isRanked());
            $diff->setDifficultyRank($rank);
            $diff->setDifficulty($difficulty->_difficulty);
            $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
            $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);

            $json2 = json_decode($jsonContent);
            $diff->setNotesCount(count($json2->_notes));
            $diff->setNotePerSecond($diff->getNotesCount() / $song->getApproximativeDuration());
            $diff->setTheoricalMaxScore($this->calculateTheoricalMaxScore($diff));
            $diff->setTheoricalMinScore($this->calculateTheoricalMinScore($diff));
            $song->addSongDifficulty($diff);
            $this->em->persist($diff);
            $allowedFiles[] = $difficulty->_beatmapFilename;
            $diff->setWanadevHash($fcrc);
        }
        if (count($previousDiffs) != 0) {
            //there is at least one update on difficulties
            foreach ($previousDiffs as $diff) {
                $diff->setSong(null);
                $this->em->remove($diff);
            }
        }

        if ($isWip != $song->getWip()) {
            $song->setCreatedAt(new DateTime());
        }
        $this->em->flush();

        /** @var UploadedFile $file */
        $patterns_flattened = implode('|', $allowedFiles);
        $infolder = strtolower(preg_replace('/[^a-zA-Z]/', '', $song->getName()));
        $zip = new ZipArchive();
        if ($zip->open($theZip) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = ($zip->getNameIndex($i));
                if (!preg_match('/'.$patterns_flattened.'/', $filename, $matches) || preg_match(
                        '/autosaves/',
                        $filename,
                        $matches
                    )) {
                    $zip->deleteName($filename);
                } else {
                    $newfilename = ($zip->getNameIndex($i));
                    $filename = ($zip->getNameIndex($i));
                    if (preg_match("/Info\.dat/", $newfilename)) {
                        $newfilename = strtolower($filename);
                    }
                    $x = explode('/', $newfilename);
                    $zip->renameName($filename, $infolder."/".$x[count($x) - 1]);
                }
            }
            $zip->close();
        }

        $coverDir = $this->kernel->getProjectDir()."/public/covers/";
        @copy($theZip, $finalFolder.$song->getId().".zip");
        @copy(
            $unzipFolder."/".$json->_coverImageFilename,
            $coverDir.$song->getId().$song->getCoverImageExtension()
        );

        $extension = strtolower($song->getCoverImageExtension());
        $source = $coverDir.$song->getId().$song->getCoverImageExtension();

        try {
            if (in_array($extension, ['.jpg', '.jpeg',])) {
                if (mime_content_type($source) !== 'image/jpeg') {
                    throw new Exception(
                        "The image cover is in the wrong format. Please verify it's a native ".$extension
                    );
                }

                $image = imagecreatefromjpeg($source);
                imagewebp($image, $coverDir.$song->getId().".webp");
                unlink($source);
                imagedestroy($image);
            } elseif (in_array($extension, ['.gif'])) {
                if (mime_content_type($source) !== 'image/gif') {
                    throw new Exception(
                        "The image cover is in the wrong format. Please verify it's a native ".$extension
                    );
                }

                $image = imagecreatefromgif($source);
                imagewebp($image, $coverDir.$song->getId().".webp");
                unlink($source);
                imagedestroy($image);
            } elseif (in_array($extension, ['.png'])) {
                if (mime_content_type($source) !== 'image/png') {
                    throw new Exception(
                        "The image cover is in the wrong format. Please verify it's a native ".$extension
                    );
                }

                $image = imagecreatefrompng($source);
                imagewebp($image, $coverDir.$song->getId().".webp");
                unlink($source);
                imagedestroy($image);
            } elseif (in_array($extension, ['.webp'])) {
            } else {
                throw new Exception("Your cover not a gif or a jpg or a png");
            }
        } catch (\Exception $e) {
            throw new Exception("The image cover is in the wrong format. Please verify it's a native ".$extension);
        }

        if (!$song->hasCover()) {
            $song->setWip(true);
        }

        if ($song->getWip()) {
            /** @var Utilisateur $user */
            foreach ($song->getMappers() as $user) {
                if ($new && $song->isPublished()) {
                    /** @var FollowMapper $follower */
                    foreach ($user->getFollowersNotifiable(
                        ENotification::Followed_mapper_new_map_wip
                    ) as $follower) {
                        $this->notificationService->send(
                            $follower->getUser(),
                            "New song : <a href='".$this->router->generate(
                                'song_detail',
                                ['slug' => $song->getSlug()]
                            )."'>[WIP] ".$song->getName()."</a> by <a href='".$this->router->generate(
                                'mapper_profile',
                                ['username' => $user->getUsername()]
                            )."'>".$user->getMapperName()."</a>"
                        );
                    }
                } else {
                    if ($song->isPublished()) {
                        /** @var FollowMapper $follower */
                        foreach ($user->getFollowersNotifiable(
                            ENotification::Followed_mapper_update_map_wip
                        ) as $follower) {
                            $this->notificationService->send(
                                $follower->getUser(),
                                "Song edit : <a href='".$this->router->generate(
                                    'song_detail',
                                    ['slug' => $song->getSlug()]
                                )."'>[WIP] ".$song->getName()."</a> by <a href='".$this->router->generate(
                                    'mapper_profile',
                                    ['username' => $user->getUsername()]
                                )."'>".$user->getMapperName()."</a>"
                            );
                        }
                    }
                }
            }
            $this->discordService->sendWipSongMessage($song);
        } elseif ($new && $song->isPublished()) {
            /** @var FollowMapper $follower */
            foreach ($song->getMappers() as $user) {
                foreach ($user->getFollowersNotifiable(ENotification::Followed_mapper_new_map) as $follower) {
                    $this->notificationService->send(
                        $follower->getUser(),
                        "New song : <a href='".
                        $this->router->generate('song_detail', ['slug' => $song->getSlug()])."'>".
                        $song->getName()."</a> by <a href='".
                        $this->router->generate('mapper_profile', ['username' => $user->getUsername()])."'>".
                        $user->getMapperName()."</a>"
                    );
                }
            }
        } else {
            if ($song->isPublished()) {
                if (!$song->isNotificationDone()) {
                    $this->discordService->sendUpdatedSongMessage($song);
                    foreach ($song->getMappers() as $user) {
                        /** @var FollowMapper $follower */
                        foreach ($user->getFollowersNotifiable(
                            ENotification::Followed_mapper_update_map
                        ) as $follower) {
                            $this->notificationService->send(
                                $follower->getUser(),
                                "Song edit : <a href='".
                                $this->router->generate('song_detail', ['slug' => $song->getSlug()])."'>".
                                $song->getName()."</a> by <a href='".
                                $this->router->generate('mapper_profile', ['username' => $user->getUsername()])."'>".
                                $user->getMapperName()."</a>"
                            );
                        }
                    }
                }

                $song->setNotificationDone(true);
                $this->em->flush();
            }
        }
    }

    function remove_utf8_bom($text)
    {
        return $this->stripUtf16Le(
            $this->stripUtf16Be($this->stripUtf8Bom($text))
        );//mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
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
                    if (is_dir($dir.DIRECTORY_SEPARATOR.$object) && !is_link($dir."/".$object)) {
                        $this->rrmdir($dir.DIRECTORY_SEPARATOR.$object);
                    } else {
                        @unlink($dir.DIRECTORY_SEPARATOR.$object);
                    }
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

        $theoricalMaxScore = ($baseSpeed * $duration) + ($noteCount * 0.3 * $baseSpeed / 4)
            - ($miss * 0.3 * $baseSpeed / 4)
            + ($maxBlueCombo * 3 / 4 * $baseSpeed)
            + ($maxYellowCombo * 3 * $baseSpeed);

        return round($theoricalMaxScore, 2);
    }

    public function calculateTheoricalMinScore(SongDifficulty $diff)
    {
        // we consider that all note were missed
        $miss = $diff->getNotesCount();
        // We consider that none combo is used
        $maxBlueCombo = 0;
        $maxYellowCombo = 0;
        // base speed of the boat given by Wanadev
        $baseSpeed = 17.18;
        $duration = $diff->getSong()->getApproximativeDuration();
        //each notes are missed = 0 hit
        $noteCount = 0;

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
        $file = $this->kernel->getProjectDir()."/public/songs-files/".$song->getId().".zip";
        $uniqBeat = "/ragna-beat/".uniqid();
        $unzipFolder = $this->kernel->getProjectDir()."/public".$uniqBeat;
        @mkdir($this->kernel->getProjectDir()."/public".$uniqBeat);
        $zip = new ZipArchive();
//        try {

        $files = [];
        $getpreview = false;
        $previewFile = "";
        $previewLocalnameFile = "";
        $songfile = "";
        try {
            if ($zip->open($file) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $elt = $zip->getFromIndex($i);
                    $exp = explode("/", $filename);
                    if (end($exp) != "") {
                        $fileinfo = pathinfo($filename);
                        $result = file_put_contents($unzipFolder."/".$fileinfo['basename'], $elt);
                        if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                            $zip->renameName($filename, strtolower($filename));
                            $song->setInfoDatFile($uniqBeat."/".$fileinfo['basename']);
                        }
                        if (preg_match("#\.ogg#isU", $fileinfo['basename'])) {
                            if (preg_match("#preview\.ogg#isU", $fileinfo['basename'])) {
                                $getpreview = true;
                            } else {
                                $songfile = $this->kernel->getProjectDir(
                                    )."/public".$uniqBeat."/".$fileinfo['basename'];
                                $previewFile = $this->kernel->getProjectDir()."/public".$uniqBeat."/preview.ogg";
                                $previewLocalnameFile = $exp[0].'/preview.ogg';
                            }
                        }
                        if (preg_match("#\.dat#isU", $fileinfo['basename'])) {
                            $files[] = $this->kernel->getProjectDir()."/public".$uniqBeat."/".$fileinfo['basename'];
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
                        'ffprobe.binaries' => '/usr/bin/ffprobe',
                    ]);
                    $probe = $ffprobe->format($songfile);
                    $durationMp3 = (int)($probe->get('duration') / 2);
                    exec(
                        'ffmpeg -y -i "'.$songfile.'"  -ss '.$durationMp3.' -t 5 -c:a copy -b:a 96k "'.$previewFile.'"'
                    );

                    $zip->addFile($previewFile, $previewLocalnameFile);
                }
                $zip->close();
            }
        } catch (Exception $e) {
            /** @todo put a sentry error */
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

    private function coverOptimisation(Song $song = null)
    {
        if ($song != null) {
            try {
                $filedir = $this->kernel->getProjectDir()."/public/covers/".$song->getId(
                    ).$song->getCoverImageExtension();

                $image = Image::make($filedir);

                $background = Image::canvas(349, 349, 'rgba(255, 255, 255, 0)');

                if ($image->width() >= $image->height()) {
                    $image->widen(349);
                } else {
                    $image->heighten(349);
                }
                $background->insert($image, 'center-center');
                $background->save($filedir);
            } catch (Exception $e) {
            }
        } else {
            $cdir = scandir($this->kernel->getProjectDir()."/public/covers");
            foreach ($cdir as $key => $value) {
                if ($value == "." || $value == "..") {
                    continue;
                }
                try {
                    $filedir = $this->kernel->getProjectDir()."/public/covers/".$value;

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
    }

    public function cleanUp(Song $song)
    {
        // remove ragnabeat
        $ragnaBeat = $this->kernel->getProjectDir()."/public/ragna-beat/";
        $infoDatFile = explode("/", $song->getInfoDatFile());
        $ragnaBeat .= $infoDatFile[2] ?? '';
        $files = glob($ragnaBeat."/*"); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                @unlink($file); // delete file
            }
        }
        @rmdir($ragnaBeat);

        // remove cover
        @unlink($this->kernel->getProjectDir()."/public/covers/".$song->getId().$song->getCoverImageExtension());
        // remove zip
        @unlink($this->kernel->getProjectDir()."/public/songs-file/".$song->getId().'.zip');
        // remove song
        $this->em->remove($song);
        $this->em->flush();
    }

    public function processFileWithoutForm(Request $request, Song $song)
    {
        try {
            $folder = $this->kernel->getProjectDir()."/public/tmp-song/";
            $unzipFolder = $folder.uniqid();
            @mkdir($unzipFolder);

            if ($request->files->get('file') != null) {
                /** @var UploadedFile $file */
                $file = $request->files->get('file');
                if ($this->security->isGranted('ROLE_PREMIUM_LVL3')) {
                    if ($file->getSize() > 30 * 1000 * 1000) {
                        throw new Exception('You can upload up to 30Mo with a premium account Tier 3');
                    }
                } elseif ($this->security->isGranted('ROLE_PREMIUM_LVL2')) {
                    if ($file->getSize() > 15 * 1000 * 1000) {
                        throw new Exception('You can upload up to 15Mo with a premium account Tier 2');
                    }
                } else {
                    if ($this->security->isGranted('ROLE_PREMIUM_LVL1')) {
                        if ($file->getSize() > 10 * 1000 * 1000) {
                            throw new Exception('You can upload up to 10Mo with a premium account Tier 1');
                        }
                    } else {
                        if ($file->getSize() > 8 * 1000 * 1000) {
                            throw new Exception('You can upload up to 10Mo with a premium account Tier 1');
                        }
                    }
                }

                $file->move($unzipFolder, $file->getClientOriginalName());
                $unzippableFile = $unzipFolder."/".$file->getClientOriginalName();
            } else {
                return false;
            }

            $this->process($unzippableFile, $unzipFolder, $song, true);
            $this->emulatorFileDispatcher($song, true);
            $this->coverOptimisation($song);
        } catch (Exception $e) {
            if (!$song->isPublished()) {
                $this->cleanUp($song);
            }

            throw new Exception($e->getMessage());
        } finally {
            $this->rrmdir($unzipFolder);
        }

        return true;
    }

    public function processExistingFile(Song $song)
    {
        try {
            $allowedFiles = [
                'preview.ogg',
                'info.dat',
                'Info.dat',
            ];
            $finalFolder = $this->kernel->getProjectDir()."/public/songs-files/";
            $folder = $this->kernel->getProjectDir()."/public/tmp-song/";
            $unzipFolder = $folder.uniqid();
            @mkdir($unzipFolder);
            $unzippableFile = $finalFolder.$song->getId().".zip";
            $zip = new ZipArchive();
            /** @var UploadedFile $file */
            if ($zip->open($unzippableFile) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $elt = $this->remove_utf8_bom($zip->getFromIndex($i));
                    $exp = explode("/", $filename);
                    if (end($exp) != "") {
                        $fileinfo = pathinfo($filename);
                        if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                            file_put_contents($unzipFolder."/".strtolower($fileinfo['basename']), $elt);
                        } else {
                            file_put_contents($unzipFolder."/".$fileinfo['basename'], $elt);
                        }
                    }
                }
                $zip->close();
            }
            $file = $unzipFolder."/info.dat";
            if (!file_exists($file)) {
                $file = $unzipFolder."/Info.dat";
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
                $rank = $this->em->getRepository(DifficultyRank::class)->findOneBy(
                    ["level" => $difficulty->_difficultyRank]
                );
                $diff = $this->em->getRepository(SongDifficulty::class)->findOneBy([
                    'song' => $song,
                    'difficultyRank' => $rank,
                ]);
                if ($diff == null) {
                    echo $song->getName()." ".$rank->getLevel()." non trouvée\r\n";
                }
                $diff->setIsRanked((bool)$diff->isRanked());
                $diff->setNoteJumpMovementSpeed($difficulty->_noteJumpMovementSpeed);
                $diff->setNoteJumpStartBeatOffset($difficulty->_noteJumpStartBeatOffset);
                $jsonContent = file_get_contents($unzipFolder."/".$difficulty->_beatmapFilename);
                $diff->setTheoricalMaxScore($this->calculateTheoricalMaxScore($diff));
                $diff->setTheoricalMinScore($this->calculateTheoricalMinScore($diff));
                $diff->setWanadevHash(Fcrc::StrCrc32($jsonContent));
                $this->em->flush();
            }

            $this->em->flush();
            $this->rrmdir($unzipFolder);

            return true;
        } catch (Exception $error) {
            echo "\r\n[Erreur]".$song->getName().": ".$error->getMessage()."\r\n\r\n";
        }

        return false;
    }

    public function getLastSongsPlayed($count)
    {
        return $this->em->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.songHashes', 'song_hashes')
            ->leftJoin(Score::class, 'score', Join::WITH, 'score.hash = song_hashes.hash')->orderBy(
                'score.updatedAt',
                'DESC'
            )->setFirstResult(0)->setMaxResults($count)->getQuery()->getResult();
    }

    public function evaluateFile(FormInterface $form)
    {
        $allowedFiles = [
            'preview.ogg',
            'info.dat',
            'Info.dat',
        ];
        $folder = $this->kernel->getProjectDir()."/public/tmp-evaluator/";
        $unzipFolder = $folder.uniqid();
        $file = $form->get('zipFile')->getData();
        $file->move($unzipFolder, $file->getClientOriginalName());
        $zip = new ZipArchive();
        $theZip = $unzipFolder."/".$file->getClientOriginalName();
        /** @var UploadedFile $file */
        if ($zip->open($theZip) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $elt = $this->remove_utf8_bom($zip->getFromIndex($i));
                $exp = explode("/", $filename);
                if (end($exp) != "") {
                    $fileinfo = pathinfo($filename);
                    if (preg_match("#info\.dat#isU", $fileinfo['basename'])) {
                        $result = file_put_contents($unzipFolder."/".strtolower($fileinfo['basename']), $elt);
                    } else {
                        $result = file_put_contents($unzipFolder."/".$fileinfo['basename'], $elt);
                    }
                }
            }
            $zip->close();
        }
        $file = $unzipFolder."/info.dat";
        if (!file_exists($file)) {
            $file = $unzipFolder."/Info.dat";
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

    public function count($addWip = false)
    {
        $qb = $this->em->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->where('s.isDeleted != true')
            ->andWhere('s.moderated = true')
            ->andWhere('s.active = true')
            ->select('COUNT(DISTINCT s.id) AS count')
            ->setFirstResult(0)->setMaxResults(1);

        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->lte('s.programmationDate', ':now'),
                $qb->expr()->isNotNull('s.programmationDate')
            )
        )
            ->setParameter('now', new DateTime());

        if (!$addWip) {
            $qb->andWhere('s.wip != true');
        }

        $songs = $qb->getQuery()->getOneOrNullResult();

        return $songs['count'];
    }

    public function getSimilarSongs(Song $song, $max = 10)
    {
        return $this->em->getRepository(Song::class)->createQueryBuilder('s')->distinct()->leftJoin(
            's.categoryTags',
            'category_tags'
        )->where("category_tags.id IN (:categories)")->andWhere('s.id != :song')->setParameter(
            'categories',
            $song->getCategoryTags()
        )->setParameter('song', $song)
//            ->orderBy('s')
            ->setMaxResults($max)->setFirstResult(0)->getQuery()->getResult();
    }

    public function getLeaderboardPosition(UserInterface $user, SongDifficulty $songDifficulty)
    {
        $mine = $this->em->getRepository(Score::class)->findOneBy([
            'user' => $user,
            'songDifficulty' => $songDifficulty,
        ]);
        if ($mine == null) {
            return "-";
        }

        return count(
                $this->em->getRepository(Score::class)
                    ->createQueryBuilder("s")
                    ->select('s.id')
                    ->where('s.rawPP > :my_score')
                    ->andWhere('s.songDifficulty = :difficulty')
                    ->andWhere('s.user != :me')
                    ->setParameter('my_score', $mine->getRawPP())
                    ->setParameter('difficulty', $songDifficulty)
                    ->setParameter('me', $user)
                    ->groupBy('s.user')
                    ->getQuery()->getResult()
            ) + 1;
    }

    public function sendNewNotification(Song $song)
    {
        if ($song->getActive() === true && $song->getProgrammationDate() <= new DateTime()) {
            $this->discordService->sendNewSongMessage($song);

            foreach ($song->getMappers() as $user) {
                foreach ($user->getFollowersNotifiable(ENotification::Followed_mapper_new_map) as $follower) {
                    $notification = new Notification();
                    $notification->setUser($follower->getUser());
                    $notification->setMessage(
                        "New song : <a href='".
                        $this->router->generate('song_detail', ['slug' => $song->getSlug()])."'>".
                        $song->getName()."</a> by <a href='".
                        $this->router->generate('mapper_profile', ['username' => $user->getUsername()])."'>".
                        $user->getMapperName()."</a>"
                    );
                    $this->em->persist($notification);
                }
            }
            $song->setNotificationDone(true);
            $this->em->flush();
        }
    }

    public function apiRender(Song $song)
    {
        return [
            "id" => $song->getId(),
            "fullname" => $song->getName(),
            "isRanked" => $song->isRanked(),
            "hash" => $song->getNewGuid(),
            "ragnabeat" => $song->getInfoDatFile(),
            "author" => [
                'fullname' => $song->getAuthorName(),
                'url' => $this->router->generate('song_library', ['search' => 'artist:'.$song->getAuthorName()]),
            ],
            "mapper" => [
                'fullname' => $song->getLevelAuthorName(),
                'url' => $this->router->generate('mapper_profile', ['username' => $song->getMapper()]),
            ],
            "levels" => $song
                ->getSongDifficulties()
                ->map(function (SongDifficulty $sd) {
                    return [
                        'rank' => $sd->getDifficultyRank()->getLevel(),
                        'color' => $sd->getDifficultyRank()->getColor(),
                    ];
                })
                ->toArray(),
            "cover" => $song->getCover(),
        ];
    }

    public function getLastPlayedToVote(Utilisateur $user): array
    {
        $qb = $this->em->getRepository(VoteCounter::class)
            ->createQueryBuilder('v')
            ->select('count(v)')
            ->where('v.song = s.id')
            ->andWhere('v.user = :user');

        $qbMapper = $this->songRepository
            ->createQueryBuilder('s2')
            ->innerJoin('s2.mappers', 'mapper')
            ->select('count(s2)')
            ->where('s2.id = s.id')
            ->andWhere('mapper.id = :user');
        try {
            $res = $this->songRepository->createQueryBuilder('s')
                ->select('s')
                ->distinct('s')
                ->leftJoin('s.songDifficulties', 'diff')
                ->leftJoin('diff.scoreHistories', 'score')
                ->andWhere($this->em->getExpressionBuilder()->eq('('.$qb->getDQL().')', '0'))
                ->andWhere($this->em->getExpressionBuilder()->eq('('.$qbMapper->getDQL().')', '0'))
                ->andWhere('score.user = :user')
                ->andWhere('s.wip = false')
                ->setParameter('user', $user)
                ->orderBy('score.updatedAt', 'DESC')
                ->setFirstResult(0)
                ->setMaxResults(4)
                ->getQuery()->getResult();

            return $res;
        } catch (\Exception $e) {
            /** @todo put a sentry error */
        }

        return [];
    }

    public function getTopRated(): array
    {
        return $this->songRepository->createQueryBuilder("s")
            ->addSelect('s, SUM(IF(v.votes_indc IS NULL,0,IF(v.votes_indc = 0,-1,1))) AS HIDDEN sum_votes')
            ->leftJoin("s.voteCounters", 'v')
            ->orderBy("sum_votes", 'DESC')
            ->where('s.isDeleted != true')
            ->andWhere('s.wip != true')
            ->andWhere('s.active = true')
            ->andWhere('(s.programmationDate <= :now)')
            ->andWhere('s.createdAt >= :date')
            ->setParameter('date', (new \DateTime())->modify('-30 days'))
            ->setParameter('now', (new \DateTime()))
            ->groupBy('s.id')
            ->setMaxResults(8)
            ->setFirstResult(0)
            ->getQuery()->getResult();

    }

    public function getLastSongs(): array
    {
        return $this->songRepository->createQueryBuilder("s")
            ->orderBy("s.lastDateUpload", 'DESC')
            ->addOrderBy("s.createdAt", 'DESC')
            ->where('s.isDeleted != true')
            ->andWhere('(s.programmationDate <= :now )')
            ->setParameter('now', (new \DateTime()))
            ->andWhere('s.wip != true')
            ->andWhere('s.active = true')
            ->setMaxResults(8)
            ->setFirstResult(0)
            ->getQuery()->getResult();

    }
}

