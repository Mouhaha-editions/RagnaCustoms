<?php


namespace App\Command;


use App\Entity\SongDifficulty;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Service\SongService;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Crc32Command extends Command
{
    protected static $defaultName = 'crc:run';
    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var SongDifficultyRepository
     */
    private $songDifficultyRepository;
    /**
     * @var SongRepository
     */
    private $songRepository;
    /**
     * @var SongService
     */
    private $songService;

    protected function configure(): void
    {
        // ...
    }

    public function __construct(KernelInterface $kernel,SongService $songService, SongRepository $songRepository)
    {
        $this->kernel = $kernel;
        $this->songService = $songService;
        $this->songRepository = $songRepository;
        return parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var SongDifficulty $song */
        foreach($this->songRepository->findBy(["isDeleted"=>false]) AS $song) {
            $this->songService->processExistingFile($song);
            echo $song->getName()."\r\n";
        }

        return Command::SUCCESS;
    }
}