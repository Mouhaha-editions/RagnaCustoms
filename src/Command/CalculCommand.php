<?php


namespace App\Command;


use App\Entity\Song;
use App\Entity\SongDifficulty;
use Doctrine\ORM\EntityManager;
use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CalculCommand extends Command
{
    protected static $defaultName = 'calcul:start';
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel, ContainerInterface $container)
    {
        $this->kernel = $kernel;
        $this->container = $container;
        return parent::__construct();
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $songs = $em->getRepository(Song::class)->createQueryBuilder('s')
            ->getQuery()->getResult();
        /** @var Song $song */
        foreach ($songs as $song) {
            $file = $this->kernel->getProjectDir() . '/public' . $song->getInfoDatFile();
            $infoFile = json_decode(file_get_contents($file));
            foreach ($infoFile->_difficultyBeatmapSets[0]->_difficultyBeatmaps as $diff) {
                $diffFile = json_decode(file_get_contents(str_replace('info.dat', $diff->_beatmapFilename, $file)));
                $rank = $diff->_difficultyRank;
                /** @var SongDifficulty $diffEntity */
                $diffEntity = $song->getSongDifficulties()->filter(function (SongDifficulty $sd) use ($rank) {
                    return $sd->getDifficultyRank()->getLevel() == $rank;
                })->first();
                $calc = round($this->calculate($diffFile, $infoFile),4);

                    $diffEntity->setClawDifficulty($calc);

                try {
                    $em->flush();
                } catch (Exception $e) {
                    var_dump("song : ".$infoFile->_songName);
                    var_dump("diff : ".$rank);
                    var_dump("calc : ".$calc);
                }
            }
        }

        return Command::SUCCESS;
    }

    private function calculate($diffFile, $infoFile)
    {
        $duration = $infoFile->_songApproximativeDuration;
        $notelist = [];
        foreach ($diffFile->_notes as $note) {
            $notelist[] = $note->_time;
        }
        $notes_per_second = count($notelist) / $duration;

        # get rid of double notes to analyze distances between runes
        $newNoteList = [];
        for ($i = 1; $i < count($notelist); $i++) {
            if (($notelist[$i] - $notelist[$i - 1]) > 0.000005) {
                $newNoteList[] = $notelist[$i - 1];
            }
        }

        $notes_without_doubles = $newNoteList;
        $distance_between_notes = [];
        for ($i = 1; $i < count($notes_without_doubles); $i++) {
            $distance_between_notes[] = $notes_without_doubles[$i] - $notes_without_doubles[$i - 1];
        }
        $standard_deviation = $this->Stand_Deviation($distance_between_notes);
        return pow($notes_per_second, 1.3) * pow($standard_deviation, 0.3);

    }

    function Stand_Deviation($arr)
    {
        $num_of_elements = count($arr);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float)sqrt($variance / $num_of_elements);
    }
}
