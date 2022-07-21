<?php

namespace App\EventListener;

use App\Controller\ControllerBase;
use App\Controller\SecurityController;
use App\Controller\ResetPasswordController;
use App\Controller\SettingController;
use App\Repository\RedirectRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ControllerListener
{
    private $redirectRepository;

    public function __construct(RedirectRepository $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        // Only handle 404 Not Found errors.
        if (!$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }

        // See if the request URI exists in the redirect table.
        $uri = $event->getRequest()->getRequestUri();
        $redirect = $this->redirectRepository->findOneByPath(urldecode($uri));
        if (!$redirect) {
            return;
        }

        // Hand the two types of redirect response.
        // RedirectResponse is used because it does extra stuff
        // (e.g. returns HTML with a meta refresh).
        $response = $redirect->isRedirect()
            ? new RedirectResponse($redirect->getDestination(), $redirect->getStatus())
            : new Response(Response::$statusTexts[$redirect->getStatus()], $redirect->getStatus());
        $event->setResponse($response);
    }

    public function onKernelController(ControllerEvent $event)
    {
        // Ignore ErrorControllers.
        if ($event->getController() instanceof ErrorController) {
            return;
        }
        /** @var ControllerBase $controller */
        $controller = $event->getController()[0];

        // Some controllers or methods are special and don't need interfering with.
        if (
            $controller instanceof SecurityController
            || $controller instanceof ResetPasswordController
            || $event->getRequest()->attributes->get('_controller') == SettingController::class . '::frontendFile'
        ) {
            return;
        }

        // Others might need special responses.
        if ($controller instanceof ControllerBase) {
            $authResponse = $controller->getAuthResponse();
            if ($authResponse) {
                $event->setController(function () use ($authResponse) {
                    return $authResponse;
                });
            }
        }
    }
}
