<?php

declare(strict_types=1);

namespace DoctrineMigrations;

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
        foreach (
            $this->songRepository->createQueryBuilder('s')
                ->select('s')
                ->leftJoin("s.songDifficulties", 'sd')
                ->where("s.isDeleted != 1")
                ->groupBy('s.id')
                ->getQuery()->getResult() 
            as $song
        ) {
            foreach ($song->getSongDifficulties() as $diff) {
                $song_file = "public/".$diff->getDifficultyFile('.');
                $notes = json_decode(file_get_contents($song_file))->_notes;
                
                // Populate all the data that will be needed for PP calculation.
                $diff->setRealMapDuration($this->songService->calculateRealMapDuration($song, $notes));
                $diff->setTheoricalMaxScore($this->songService->calculateTheoricalMaxScore($diff));
                $diff->setTheoricalMinScore($this->songService->calculateTheoricalMinScore($diff));
                $diff->setEstAvgAccuracy($this->songService->calculateEstAvgAccuracy($diff, $notes));
                $diff->setPPCurveMax($this->songService->calculatePPCurveMax($diff));

                $this->songDifficultyRepository->add($diff, true);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
