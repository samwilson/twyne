<?php

namespace App\EventListener;

use App\Controller\ControllerBase;
use App\Controller\ResetPasswordController;
use App\Controller\SecurityController;
use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{

    public function onKernelController(ControllerEvent $event)
    {
        // Ignore ErrorControllers.
        if ($event->getController() instanceof ErrorController) {
            return;
        }
        /** @var ControllerBase $controller */
        $controller = $event->getController()[0];

        // Some controllers are special and don't need interfering with.
        if (
            $controller instanceof SecurityController
            || $controller instanceof ResetPasswordController
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
