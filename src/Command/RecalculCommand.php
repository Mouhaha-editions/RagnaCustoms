<?php


namespace App\Command;


use App\Entity\RankedScores;
use App\Entity\Score;
use App\Repository\RankedScoresRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RankingScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
        $cUsers = count($users);
$section1 = $output->section();
$section2 = $output->section();
        $userProgress = new ProgressBar($section1, $cUsers);
        $j = 0;
        $cUsers = count($users);
        foreach ($users as $k=>$user) {
            $j++;
            $scores = $user->getScores()->filter(function (Score $score) use($plateform) {
                return $score->isRankable() && (($plateform == 'vr' && $score->isVR()) || ($plateform == 'flat' && !$score->isVR())) && $score->getPlateform() != null;
            });
            /** @var Score $score */
            $i = 0;

            if($scores->count() == 0){
                unset($users[$k]);
                continue;
            }
            $scoreProgress = new ProgressBar($section2, $scores->count());

            foreach ($scores as $score) {
                $i++;
                $this->rankingScoreService->calculateRawPP($score);
                $scoreProgress->advance();
            }
            $this->entityManager->flush();
            $this->rankingScoreService->calculateTotalPondPPScore($user, $plateform == 'vr');
            $scoreProgress->finish();

            unset($users[$k]);
            $userProgress->advance();

        }
        $userProgress->finish();

        return Command::SUCCESS;
    }
}