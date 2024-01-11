<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\RankingScoreService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ranking:math')]
class RecalculateCommand extends Command
{
    public function __construct(
        private readonly RankingScoreService $rankingScoreService,
        private readonly UtilisateurRepository $utilisateurRepository
    ) {
        return parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'The username of the user.')
            ->addOption('user-id', 'u-id', InputOption::VALUE_OPTIONAL, 'The Id of the user.')
            ->addOption('plateform', 'p', InputOption::VALUE_OPTIONAL, 'plateform to calculate vr(default) or flat.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getOption('username');
        $user_id = $input->getOption('user-id');
        $plateform = $input->getOption('plateform') ?? 'vr';

        $qb = $this->utilisateurRepository
            ->createQueryBuilder('u')
            ->leftJoin('u.scores', 'score')
            ->andWhere('score.id IS NOT NULL');

        if ($username) {
            $qb->andWhere('u.username = :username')
                ->setParameter('username', $username);
        } elseif ($user_id) {
            $qb->andWhere('u.id = :id')
                ->setParameter('id', $user_id);
        }

        $users = $qb->getQuery()->getResult();

        $cUsers = count($users);
        $section1 = $output->section();
        $section2 = $output->section();
        $userProgress = new ProgressBar($section1, $cUsers, 1 / 100);
        /**
         * @var Utilisateur $user
         */
        //39209
        foreach ($users as $k => $user) {
            $this->rankingScoreService->calculateTotalPondPPScore($user, $plateform == 'vr');
            unset($users[$k]);
            $userProgress->advance();
        }

        $userProgress->finish();

        return Command::SUCCESS;
    }
}
