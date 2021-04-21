<?php

namespace App\Service;

use App\Entity\Song;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
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
        $uniqBeat="/ragna-beat/".uniqid();
        $unzipFolder = $this->kernel->getProjectDir() . "/public".$uniqBeat;
        mkdir($this->kernel->getProjectDir() . "/public".$uniqBeat);
        $zip = new ZipArchive();
        try {

            if ($zip->open($file) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $elt = $zip->getFromIndex($i);
                    $exp = explode("/", $filename);
                    if (end($exp) != "") {
                        $fileinfo = pathinfo($filename);
                        $result = file_put_contents($unzipFolder . "/" . $fileinfo['basename'], $elt);
                        if(preg_match("#info\.dat#isU", $fileinfo['basename'])){
                            $song->setInfoDatFile( $uniqBeat."/" . $fileinfo['basename']);
                        }
                    }
                }
//                $filename = $song->getInfoDatFile();
//                $handle = fopen($filename, "rb");
//                $fsize = filesize($filename);
//                $contents = fread($handle, $fsize);
//                $byteArray = unpack("N*",$contents);


                $this->em->flush();

                $zip->close();
            }
        }catch (\Exception $e){

        }
    }
}

