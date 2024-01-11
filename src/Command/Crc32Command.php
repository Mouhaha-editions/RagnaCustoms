<?php

namespace App\Command;

use App\Entity\SongDifficulty;
use App\Repository\SongRepository;
use App\Service\SongService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'crc:run')]
class Crc32Command extends Command
{
    public function __construct(
        private readonly SongService $songService,
        private readonly SongRepository $songRepository
    ) {
        return parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var SongDifficulty $song */
        foreach ($this->songRepository->findBy(["isDeleted" => false], ['name' => 'DESC']) as $song) {
            $this->songService->processExistingFile($song);
            echo $song->getName()."\r\n";
        }

        return Command::SUCCESS;
    }
}
