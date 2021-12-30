<?php


namespace App\Command;


use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Service\ScoreService;
use Doctrine\ORM\EntityManager;
use Exception;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CalculCommand extends Command
{
    protected static $defaultName = 'calcul:start';
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel, ContainerInterface $container, ScoreService $scoreService)
    {
        $this->kernel = $kernel;
        $this->container = $container;
        $this->scoreService = $scoreService;
        return parent::__construct();
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $songs = $em->getRepository(Song::class)->createQueryBuilder('s')
            ->where("s.approximativeDuration > 60")
            ->andWhere('s.isDeleted != 1')
            ->getQuery()->getResult();
        /** @var Song $song */
        foreach ($songs as $song) {
            $this->scoreService->ClawwMethod($song);
        }

        return Command::SUCCESS;
    }


}
