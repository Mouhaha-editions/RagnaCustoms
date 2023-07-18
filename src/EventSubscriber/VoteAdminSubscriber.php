<?php


namespace App\EventSubscriber;


use App\Entity\Vote;
use App\Entity\VoteCounter;
use App\Repository\SongRepository;
use App\Service\SongService;
use App\Service\VoteService;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\VarDumper\VarDumper;

class VoteAdminSubscriber implements EventSubscriberInterface
{


    public function __construct(
        public SongService $songService,
        public VoteService $voteService,
        public SongRepository $songRepository,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterEntityUpdatedEvent::class => ['sendMail'],
            BeforeEntityDeletedEvent::class => ['beforeDelete'],
        ];
    }

    public function beforeDelete(BeforeEntityDeletedEvent $event)
    {
        /** @var Vote $entity */
        $entity = $event->getEntityInstance();

        if ($entity instanceof Vote) {
            $song = $entity->getSong();
            $this->voteService->subScore($song, $entity);
            $this->songRepository->add($song);
        }
        if ($entity instanceof VoteCounter) {
//            if($entity->getVotesIndc() > 0){
//
//            }
        }
    }

    public function sendMail(AfterEntityUpdatedEvent $event)
    {
        $entity = $event->getEntityInstance();

        if ($entity instanceof Vote) {
            $song = $entity->getSong();

            if ($song->getUser()->getEnableEmailNotification() && $entity->getIsModerated()) {
                $this->songService->newFeedbackForMapper($entity);
            }
        }
    }
}