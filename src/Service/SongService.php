<?php

namespace App\Service;

use App\Entity\Song;
use App\Entity\SongFeedback;
use App\Helper\AIMapper;
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
                $filename = $song->getInfoDatFile();
                $song->setGuid(md5_file($this->kernel->getProjectDir() . "/public/" . $filename));
                $song->setNewGuid($this->HashSong($files));
                $this->em->flush();

                if (!$getpreview) {
                    $ffprobe = FFProbe::create([
                        'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                        'ffprobe.binaries' => '/usr/bin/ffprobe'
                    ]);
                    $probe = $ffprobe->format($songfile);
                    $durationMp3 = (int)($probe->get('duration') / 2);

//                    ffmpeg -y -i <input.ogg> -ss 130 -t 5 -c:a copy -b:a 96k <output.ogg>


                    exec('ffmpeg -y -i "' . $songfile . '"  -ss ' . $durationMp3 . ' -t 5 -c:a copy -b:a 96k "' . $previewFile . '"');
//                    $bitrate = $probe->get('bit_rate');
//                    $ffmpeg = FFMpeg::create([
////                    'ffmpeg.binaries' => '/usr/bin/ffmpeg',
////                    'ffprobe.binaries' => '/usr/bin/ffprobe'
//                    ]);
//                    $audio = $ffmpeg->open($songfile);
//                    if ($durationMp3 > 8) {
//                        $start = $durationMp3 / 2;
//                        $audio->filters()->clip($start, 8);
//                    } else {
//                        $audio->filters()->clip(TimeCode::fromSeconds(0), TimeCode::fromSeconds($durationMp3));
//                    }
//                    $format = new Vorbis();
//                    $format->setAudioKiloBitrate($bitrate);
//                    $audio->save($format, $previewFile);

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
        $song = $feedback->getSong();
        $email = (new Email())
            ->from('contact@ragnacustoms.com')
            ->to('pierrick.pobelle@gmail.com')
            ->addBcc()
            ->subject('New feedback for ' . $song->getName() . '!');
        $email->html("New feedback");
        $this->mailer->send($email);
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
}

