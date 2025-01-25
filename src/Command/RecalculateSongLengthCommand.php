<?php

namespace App\Command;

use App\Controller\WanadevApiController;
use App\Entity\Song;
use App\Entity\Utilisateur;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RankingScoreService;
use App\Service\SongService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'song:length')]
class RecalculateSongLengthCommand extends Command
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly SongService $songService,
        private readonly SongDifficultyRepository $songDifficultyRepository
    ) {
        return parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Song $song */
        $songs = $this->songRepository->createQueryBuilder('s')
            ->select('s')
            ->leftJoin("s.songDifficulties", 'sd')
            ->where("s.isDeleted != 1")
            ->andWhere("sd.estAvgAccuracy IS NULL")
            ->groupBy('s.id')
            ->getQuery()->getResult();
        $progress = new ProgressBar($output, count($songs));

        foreach ($songs as $song) {
            foreach ($song->getSongDifficulties() as $diff) {
                $song_file = "public/".$diff->getDifficultyFile('.');
                // var_dump($song_file);
                var_dump($song->getId().' '.$song->getInfoDatFile());

                if (!file_exists($song_file)) {
                    continue;
                }

                try {
                    $notes = json_decode(file_get_contents($song_file))->_notes;

                    // Populate all the data that will be needed for PP calculation.
                    $diff->setRealMapDuration($this->songService->calculateRealMapDuration($song, $notes));
                    $diff->setTheoricalMaxScore($this->songService->calculateTheoricalMaxScore($diff));
                    $diff->setTheoricalMinScore($this->songService->calculateTheoricalMinScore($diff));
                    // We already saw that the estimation formula doesn't represent some songs well - exceptions decided manually based on player scores
                    switch ($diff->getId()) {
                        case 3213: // Pipi vs. caca - Ultra Vomit - 4
                            $diff->setEstAvgAccuracy(84);
                            break;
                        case 3214: // Pipi vs. caca - Ultra Vomit - 7
                            $diff->setEstAvgAccuracy(75);
                            break;
                        case 3215: // Pipi vs. caca - Ultra Vomit - 9
                            $diff->setEstAvgAccuracy(73);
                            break;
                        case 1722: // UnAlive (Short Edit) - Mori Calliope - 6
                            $diff->setEstAvgAccuracy(83);
                            break;
                        case 937:  // Eyeless - Slipknot - 10
                            $diff->setEstAvgAccuracy(71);
                            break;
                        case 124:  // Played-A-Live (The Bongo Song) - Safri Duo - 9
                            $diff->setEstAvgAccuracy(73);
                            break;
                        case 133:  // Diggy Diggy Hole - Wind Rose - 8
                            $diff->setEstAvgAccuracy(77);
                            break;
                        case 141:  // Kammthaar - Ultra Vomit - 10
                            $diff->setEstAvgAccuracy(75);
                            break;
                        case 187:  // Run Boy Run - Woodkid - 8
                            $diff->setEstAvgAccuracy(75);
                            break;
                        case 405:  // Sandstorm - Darude - 9
                            $diff->setEstAvgAccuracy(72);
                            break;
                        case 218:  // Toss a Coin To Your Witcher - Skar - 10
                            $diff->setEstAvgAccuracy(74);
                            break;
                        case 2405: // Through The Fire and Flames - DragonForce - 10
                            $diff->setEstAvgAccuracy(64);
                            break;
                        default:
                            $diff->setEstAvgAccuracy($this->songService->calculateEstAvgAccuracy($diff, $notes));
                            break;
                    }
                    $diff->setPPCurveMax($this->songService->calculatePPCurveMax($diff));

                    $this->songDifficultyRepository->add($diff, true);
                } catch (Exception $exception) {
                    echo $diff." ERROR: ".$exception->getMessage()."\r\n";
                }
            }
            $progress->advance();
        }

        return Command::SUCCESS;
    }
}
