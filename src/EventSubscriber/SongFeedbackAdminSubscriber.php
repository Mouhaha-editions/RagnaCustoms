<?php


namespace App\EventSubscriber;


use App\Entity\SongFeedback;
use App\Service\SongService;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;

class SongFeedbackAdminSubscriber implements EventSubscriberInterface
{


    /**
     * @var SongService
     */
    private $songService;

    public function __construct(SongService $songService)
    {
        $this->songService = $songService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterEntityUpdatedEvent::class => ['sendMail'],
        ];
    }

    public function sendMail(AfterEntityUpdatedEvent $event)
    {

        /** @var SongFeedback $entity */
        $entity = $event->getEntityInstance();
        if(get_class($entity) == SongFeedback::class){
            $song = $entity->getSong();
            if($song->getUser()->getEnableEmailNotification() && $entity->getIsModerated()) {
                $this->songService->newFeedbackForMapper($entity);
            }
        }

    }
}