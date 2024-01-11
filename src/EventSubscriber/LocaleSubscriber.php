<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use App\Security\AccountNotVerifiedAuthenticationException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class LocaleSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly string $defaultLocale = 'en'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before (i.e. with a higher priority than) the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        /** @var Utilisateur $user */
        $user = $event->getPassport()->getUser();

        if (!$user->isVerified()) {
            throw new AccountNotVerifiedAuthenticationException();
        }
    }


}
