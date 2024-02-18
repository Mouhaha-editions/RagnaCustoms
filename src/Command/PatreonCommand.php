<?php

namespace App\Command;

use App\Entity\Song;
use App\Repository\SongRepository;
use App\Service\PatreonService;
use App\Service\SongService;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'patreon:check')]
class PatreonCommand extends Command
{
    public function __construct(private PatreonService $patreonService)
    {
        return parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
       $this->patreonService->getCampaigns();

        return Command::SUCCESS;
    }
}
