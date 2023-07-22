<?php

namespace App\EventSubscriber;

use App\Security\AccountNotVerifiedAuthenticationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class CheckVerifiedUserSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router, )
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            KernelEvents::REQUEST => 'onKernelRequest',

        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

        if ($user && !$user->isVerified()) {
            $this->tokenStorage->setToken(null);
            // L'utilisateur est connecté mais n'est pas vérifié, vous pouvez rediriger vers une page spécifique ou une page de vérification.
            $event->setResponse(new RedirectResponse($this->router->generate('app_verify_resend_email')));
        }
    }

    public function onLoginFailure(LoginFailureEvent $event)
    {
        if (!$event->getException() instanceof AccountNotVerifiedAuthenticationException) {
            return;
        }

        $response = new RedirectResponse(
            $this->router->generate('app_verify_resend_email')
        );
        $event->setResponse($response);
    }
}