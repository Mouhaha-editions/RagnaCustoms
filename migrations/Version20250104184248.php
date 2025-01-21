<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Song;
use Exception;
use App\Contract\SongAwareMigrationInterface;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Service\SongService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250104184248 extends AbstractMigration implements SongAwareMigrationInterface
{
    private $songDifficultyRepository;
    private $songRepository;
    private $songService;

    public function setSongDifficultyRepository(SongDifficultyRepository $songDifficultyRepository): void
    {
        $this->songDifficultyRepository = $songDifficultyRepository;
    }

    public function setSongRepository(SongRepository $songRepository): void
    {
        $this->songRepository = $songRepository;
    }

    public function setSongService(SongService $songService): void
    {
        $this->songService = $songService;
    }

    public function getDescription(): string
    {
        return 'Populates new columns on song diffs with data needed for PP calculation after rework';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        /** @var Song $song */
        foreach (
            $this->songRepository->createQueryBuilder('s')
                ->select('s')
                ->leftJoin("s.songDifficulties", 'sd')
                ->where("s.isDeleted != 1")
                ->andWhere("sd.estAvgAccuracy IS NULL")
                ->groupBy('s.id')
                ->getQuery()->getResult()
            as $song
        ) {
            foreach ($song->getSongDifficulties() as $diff) {
                $song_file = "public/".$diff->getDifficultyFile('.');
                // var_dump($song_file);
                if (!file_exists($song_file)) {
                    continue;
                }

                var_dump($song->getId().' '.$song->getInfoDatFile());

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
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
