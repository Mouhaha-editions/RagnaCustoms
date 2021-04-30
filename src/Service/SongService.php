<?php

namespace App\Service;

use App\Entity\Song;
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
use Symfony\Component\VarDumper\VarDumper;
use ZipArchive;

class SongService
{
    private $kernel;
    private $em;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em)
    {
        $this->kernel = $kernel;
        $this->em = $em;
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
                    VarDumper::dump($song->getId());
                    $ffprobe = FFProbe::create([
                    'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                    'ffprobe.binaries' => '/usr/bin/ffprobe'
                    ]);
                    $durationMp3 = $ffprobe->format($songfile)->get('duration');
                    $ffmpeg = FFMpeg::create([
                    'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                    'ffprobe.binaries' => '/usr/bin/ffprobe'
                    ]);
                    $audio = $ffmpeg->open($songfile);
                    if ($durationMp3 > 8) {
                        $start = $durationMp3 / 2;
                        $audio->filters()->clip(TimeCode::fromSeconds($start), TimeCode::fromSeconds(8));
                    } else {
                        $audio->filters()->clip(TimeCode::fromSeconds(0), TimeCode::fromSeconds($durationMp3));
                    }
                    $format = new Vorbis();
                    $audio->save($format, $previewFile);
                    $zip->addFile($previewFile, $previewLocalnameFile);
                }
                $zip->close();
            }
        } catch(Exception $e) {
            VarDumper::dump($song->getId());
        }

    }

    public function HashSong(array $files)
    {
        $str = "";
        foreach ($files as $file) {
            $str .= md5_file($file);
        }
        return md5($str);
    }
}

