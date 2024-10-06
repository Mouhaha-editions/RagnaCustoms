<?php

namespace App\Command;

use App\Controller\WanadevApiController;
use App\Entity\SongTemporaryList;
use App\Entity\Utilisateur;
use App\Repository\SongTemporaryListRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RankingScoreService;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'clean:tmp-list')]
class CleanTempListCommand extends Command
{
    public function __construct(
        private readonly SongTemporaryListRepository $songTemporaryListRepository
    ) {
        return parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lists = $this->songTemporaryListRepository->createQueryBuilder('l')
            ->where('l.createdAt <= :date')
            ->setParameter('date', (new DateTime())->modify('-1 days'))
            ->getQuery()
            ->getResult();

        foreach ($lists as $list) {
            $this->songTemporaryListRepository->remove($list);
        }

        return Command::SUCCESS;
    }
}
