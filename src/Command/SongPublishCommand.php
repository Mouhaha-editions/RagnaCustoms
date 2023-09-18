<?php


namespace App\Command;


use App\Entity\Song;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Service\SongService;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class SongPublishCommand extends Command
{
    protected static $defaultName = 'song:publish';
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

    public function __construct(SongService $songService, SongRepository $songRepository)
    {
        $this->songService = $songService;
        $this->songRepository = $songRepository;
        return parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $songs = $this->songRepository
            ->createQueryBuilder('s')
            ->where("s.active = 1")
            ->andWhere("s.wip = 0")
            ->andWhere("s.isNotificationDone = 0")
            ->andWhere("s.programmationDate <= :now")
            ->setParameter('now', new DateTime())
            ->getQuery()->getResult();
        /** @var Song $song */
        foreach ($songs as $song) {
            if ($song->getLastDateUpload() === null) {
                $this->songService->sendNewNotification($song);
            }

            $song->setLastDateUpload(new DateTime());
            $this->songRepository->add($song);
            echo $song->getName()."\r\n";
        }

        return Command::SUCCESS;
    }
}