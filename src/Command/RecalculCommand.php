<?php


namespace App\Command;


use App\Entity\RankedScores;
use App\Entity\Score;
use App\Repository\RankedScoresRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RankingScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RecalculCommand extends Command
{
    protected static $defaultName = 'ranking:math';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RankingScoreService $rankingScoreService,
        private RankedScoresRepository $rankedScoresRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {
        return parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', 'u',InputOption::VALUE_OPTIONAL, 'The username of the user.')
            ->addOption('user-id', 'u-id',InputOption::VALUE_OPTIONAL, 'The Id of the user.')
            ->addOption('plateform', 'p',InputOption::VALUE_OPTIONAL, 'plateform to calculate vr(default) or flat.');
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getOption('username');
        $user_id = $input->getOption('user-id');
        $plateform = $input->getOption('plateform') ?? 'vr';

        if ($username) {
            $users = $this->utilisateurRepository->findBy(['username' => $username]);
        } elseif ($user_id) {
            $users = $this->utilisateurRepository->findBy(['id' => $user_id]);
        } else {
            $users = $this->utilisateurRepository->findAll();
        }

        $j = 0;
        $cUsers = count($users);
        foreach ($users as $user) {
            $j++;
            echo "start: ".$user->getUsername()." (".$j."/".$cUsers.")\r\n";
            $scores = $user->getScores()->filter(function (Score $score) {
                return $score->isRankable();
            });
            echo "scores: ".count($scores)."\r\n";
            /** @var Score $score */
            $i = 0;

            foreach ($scores as $score) {
                $i++;
                echo "score: ".($i)."/".count($scores)."\r\n";
                $this->rankingScoreService->calculateRawPP($score);
            }

            $this->entityManager->flush();

            $totalPondPPScore = $this->rankingScoreService->calculateTotalPondPPScore($user, $plateform == 'vr');
            //insert/update of the score into ranked_scores
            $rankedScore = $this->rankedScoresRepository->findOneBy([
                'user' => $user,
                'plateform' => $plateform
            ]);

            if ($rankedScore == null) {
                $rankedScore = new RankedScores();
                $rankedScore->setUser($user);
                $rankedScore->setPlateform($plateform);
                $this->entityManager->persist($rankedScore);
            }
            $rankedScore->setTotalPPScore($totalPondPPScore);
            echo "save: ".$user->getUsername()."\r\n";
            $this->entityManager->flush();
            echo "end: ".$user->getUsername()."\r\n";
        }

        return Command::SUCCESS;
    }
}